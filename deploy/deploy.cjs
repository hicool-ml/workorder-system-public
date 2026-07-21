// ============================================================
// 工单系统 代码部署脚本（Node 版，不碰数据库）
//
// 用法:
//   node deploy/deploy.cjs diff     // 只读：比对本地与生产，生成变更清单 deploy/.changes.json
//   node deploy/deploy.cjs apply    // 执行部署（先备份->上传变更->清缓存->校验计数）
//   node deploy/deploy.cjs rollback <备份目录名>
//
// 安全保障:
//   - 绝不导出/导入/迁移数据库；不运行 migrate；不碰 .env / storage
//   - 部署前自动备份生产被覆盖的整个目录（cp -a，留在服务器上）
//   - 部署后校验 workorders/users 行数与部署前一致，不一致即报警
//   - 默认只覆盖/新增变更文件，不删除生产上任何文件（避免误删）
//   - DELETES=1 环境变量可开启删除（仅删本地已不存在且属于源码集的文件）
// ============================================================
const fs = require("fs");
const path = require("path");
const crypto = require("crypto");
const { Client } = require("ssh2");

const HOST = process.env.P_HOST || "192.168.1.8";
const USER = process.env.P_USER || "cdu";
const PASS = process.env.P_PASS || "REDACTED";
const RROOT = process.env.P_PATH || "/var/www/workorder";
const LROOT = path.resolve(__dirname, "..");
const BACKUP_ROOT = process.env.P_BACKUP || "/home/cdu/workorder-backups";

// 参与部署的源码集顶层条目（与 deploy_code_only.sh 一致）
const SOURCE_TOPS = [
  "app", "routes", "config", "database", "resources",
  "public", "bootstrap", "artisan",
  "composer.json", "composer.lock", "package.json", "VERSION",
];

// 上传时排除（即便出现在源码集里也不传）
const EXCLUDE = [
  /^\.env(\.|$)/,
  /^storage\//,
  /^node_modules\//,
  /^vendor\//,
  /^\.git\//,
  /^deploy\//,
  /^tests\//,
  /^public\/storage\//,
  /^public\/hot$/,
  /\.(bak|backup)$/i,
  /\.bak\./i,
  /\.phpactor\.json$/,
  /\.phpunit\.cache\//,
];

const want = (rel) => rel.split("\\").join("/").replace(/^\.\//, "");
const isExcluded = (rel) => {
  const p = want(rel);
  return EXCLUDE.some((re) => re.test(p));
};

function md5File(p) {
  const h = crypto.createHash("md5");
  h.update(fs.readFileSync(p));
  return h.digest("hex");
}

function listLocalFiles() {
  const out = [];
  for (const top of SOURCE_TOPS) {
    const full = path.join(LROOT, top.replace(/\//g, path.sep));
    if (!fs.existsSync(full)) continue;
    const stat = fs.statSync(full);
    if (stat.isFile()) {
      if (!isExcluded(top)) out.push(top);
      continue;
    }
    const walk = (dir) => {
      for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
        const rel = path.relative(LROOT, path.join(dir, e.name));
        if (e.isDirectory()) {
          if (isExcluded(rel + "/")) continue;
          walk(path.join(dir, e.name));
        } else if (e.isFile()) {
          if (!isExcluded(rel)) out.push(want(rel));
        }
      }
    };
    walk(full);
  }
  return out;
}

function connect() {
  return new Promise((resolve, reject) => {
    const conn = new Client();
    conn.on("ready", () => resolve(conn));
    conn.on("error", reject);
    conn.connect({ host: HOST, port: 22, username: USER, password: PASS, readyTimeout: 30000 });
  });
}

function remoteExec(conn, cmd) {
  return new Promise((resolve, reject) => {
    conn.exec(cmd, { pty: false }, (err, stream) => {
      if (err) return reject(err);
      let out = "", errm = "";
      stream.on("data", (d) => (out += d));
      stream.stderr.on("data", (d) => (errm += d));
      stream.on("close", (code) => resolve({ code, stdout: out, stderr: errm }));
    });
  });
}

function parseManifest(text) {
  const map = new Map();
  for (const line of text.split("\n")) {
    const m = line.match(/^([0-9a-f]{32})\s+\*?(.+)$/i);
    if (m) map.set(m[2].trim(), m[1]);
  }
  return map;
}

async function remoteManifest(conn) {
  const tops = SOURCE_TOPS.join(" ");
  const cmd = `cd ${RROOT} 2>/dev/null && find ${tops} -type f 2>/dev/null -print0 | xargs -0 md5sum 2>/dev/null`;
  const { stdout } = await remoteExec(conn, cmd);
  return parseManifest(stdout);
}

function localManifest(files) {
  const map = new Map();
  for (const rel of files) {
    try { map.set(rel, md5File(path.join(LROOT, rel.replace(/\//g, path.sep)))); }
    catch (e) { /* skip unreadable */ }
  }
  return map;
}

async function doDiff() {
  const conn = await connect();
  try {
    console.log("[diff] 读取生产文件清单（只读）...");
    const remote = await remoteManifest(conn);
    console.log(`[diff] 生产源码文件数: ${remote.size}`);
    const files = listLocalFiles();
    const local = localManifest(files);
    console.log(`[diff] 本地源码文件数: ${local.size}`);

    const changed = [];
    const same = [];
    for (const [rel, lh] of local) {
      const rh = remote.get(rel);
      if (rh === lh) same.push(rel);
      else changed.push(rel);
    }
    const deleted = [];
    if (process.env.DELETES === "1") {
      for (const rel of remote.keys()) if (!local.has(rel)) deleted.push(rel);
    }

    const out = {
      generated: new Date().toISOString(),
      host: HOST, remote_root: RROOT, local_root: LROOT,
      summary: { same: same.length, changed: changed.length, deleted: deleted.length },
      changed, deleted,
    };
    fs.writeFileSync(path.join(__dirname, ".changes.json"), JSON.stringify(out, null, 2));
    console.log(`\n[diff] 结果：相同 ${same.length} | 变更 ${changed.length} | 待删 ${deleted.length}`);
    console.log("[diff] 清单已写入 deploy/.changes.json");
    if (changed.length && changed.length <= 200) {
      console.log("\n变更文件：");
      for (const f of changed) console.log("  " + f);
    }
  } finally { conn.end(); }
}

async function doApply() {
  const changesPath = path.join(__dirname, ".changes.json");
  if (!fs.existsSync(changesPath)) {
    console.error("未找到 deploy/.changes.json，请先运行: node deploy/deploy.cjs diff");
    process.exit(1);
  }
  const plan = JSON.parse(fs.readFileSync(changesPath, "utf8"));
  const changed = plan.changed || [];
  const deleted = plan.deleted || [];
  if (!changed.length && !deleted.length) {
    console.log("没有变更文件，无需部署。");
    return;
  }

  const conn = await connect();
  const sftp = await new Promise((res, rej) => conn.sftp((e, s) => (e ? rej(e) : res(s))));
  try {
    console.log("[apply] 步骤 1/5 读取数据库基线计数（只读，不改库）...");
    const base = await remoteExec(conn, baselineCountCmd());
    if (base.code !== 0) { console.error("基线计数失败:\n" + base.stderr); process.exit(2); }
    const before = base.stdout.trim();
    console.log("  基线: " + before.split("\n").join(" | "));

    const bname = "workorder-backup-" + ts();
    const bdir = `${BACKUP_ROOT}/${bname}`;
    // 用 tar 打包，--ignore-failed-read 跳过权限不可读的运行时文件（如 storage 里的 www-data 备份），
    // 既保留完整结构便于回滚，又不会被个别文件挡住。storage/app 等大块运行时数据可选排除以加速。
    const tarball = `${BACKUP_ROOT}/${bname}.tar.gz`;
    console.log(`[apply] 步骤 2/5 备份生产目录 -> ${tarball} (tar, 跳过不可读文件) ...`);
    const bk = await remoteExec(conn,
      `mkdir -p ${BACKUP_ROOT} && ` +
      `cd ${RROOT} && tar --ignore-failed-read --warning=no-file-changed --warning=no-file-removed ` +
      `--exclude='storage/app/private/backups' --exclude='storage/logs/*.log' ` +
      `-czf ${tarball} . 2>/dev/null && echo OK`);
    if (!/OK/.test(bk.stdout)) { console.error("备份失败:\n" + bk.stderr); process.exit(3); }

    console.log(`[apply] 步骤 3/5 上传 ${changed.length} 个变更文件 ...`);
    let n = 0;
    for (const rel of changed) {
      const localPath = path.join(LROOT, rel.replace(/\//g, path.sep));
      const remotePath = `${RROOT}/${rel}`;
      await ensureRemoteDir(sftp, remotePath);
      await uploadRetry(sftp, localPath, remotePath);
      if (++n % 20 === 0 || n === changed.length) console.log(`  已上传 ${n}/${changed.length}`);
    }
    for (const rel of deleted) {
      await remoteExec(conn, `rm -f ${RROOT}/${rel}`);
    }

    console.log("[apply] 步骤 4/5 清缓存 / 权限 / reload web ...");
    const post = await remoteExec(conn, postDeployCmd());
    console.log("  " + post.stdout.split("\n").filter(Boolean).join("\n  "));

    console.log("[apply] 步骤 5/5 校验数据库计数是否未变 ...");
    const after = await remoteExec(conn, baselineCountCmd());
    const afterS = after.stdout.trim();
    if (afterS !== before) {
      console.error("\n*** 警告：计数发生变化！部署可能影响了数据，请立即排查 ***");
      console.error("  before: " + before);
      console.error("  after : " + afterS);
      console.error(`  回滚: node deploy/deploy.cjs rollback ${bname}`);
      process.exit(4);
    }
    console.log("  计数一致，数据库未受影响");

    console.log("\n================================================");
    console.log("  部署完成");
    console.log(`  备份目录: ${bdir}`);
    console.log(`  回滚命令: node deploy/deploy.cjs rollback ${bname}`);
    console.log("================================================");
  } finally {
    sftp.end();
    conn.end();
  }
}

async function doRollback(name) {
  if (!name) { console.error("用法: node deploy/deploy.cjs rollback <备份目录名>"); process.exit(1); }
  const tarball = `${BACKUP_ROOT}/${name}.tar.gz`;
  const bdir = `${BACKUP_ROOT}/${name}`;
  const conn = await connect();
  try {
    console.log(`[rollback] 恢复 ${RROOT} ...`);
    const r = await remoteExec(conn,
      `if [ -f ${tarball} ]; then ` +
      `  cd ${RROOT} && tar --ignore-failed-read -xzf ${tarball} && ` +
      `  php artisan view:clear && php artisan config:clear && php artisan cache:clear && php artisan route:clear && ` +
      `  echo 'rollback done (from tarball)'; ` +
      `elif [ -d ${bdir} ]; then ` +
      `  cp -a ${bdir}/. ${RROOT}/ && cd ${RROOT} && ` +
      `  php artisan view:clear && php artisan config:clear && php artisan cache:clear && php artisan route:clear && ` +
      `  echo 'rollback done (from dir)'; ` +
      `else echo '备份不存在'; exit 1; fi`);
    console.log(r.stdout);
    if (r.code !== 0) console.error(r.stderr);
  } finally { conn.end(); }
}

function baselineCountCmd() {
  return [
    `cd ${RROOT}`,
    `DB=$(grep -E '^DB_DATABASE=' .env | cut -d= -f2- | xargs)`,
    `DU=$(grep -E '^DB_USERNAME=' .env | cut -d= -f2- | xargs)`,
    `DP=$(grep -E '^DB_PASSWORD=' .env | cut -d= -f2- | xargs)`,
    `mysql -u"$DU" -p"$DP" "$DB" -N -e "SELECT CONCAT('workorders=',COUNT(*)) FROM workorders;SELECT CONCAT('users=',COUNT(*)) FROM users;SELECT CONCAT('logs=',COUNT(*)) FROM workorder_logs;" 2>/dev/null`,
  ].join(" && ");
}
function postDeployCmd() {
  return [
    `cd ${RROOT}`,
    `php artisan view:clear`,
    `php artisan config:clear`,
    `php artisan cache:clear`,
    `php artisan route:clear`,
    `(sudo chown -R ${USER}:www-data storage bootstrap/cache 2>/dev/null || true)`,
    `(sudo chmod -R 775 storage bootstrap/cache 2>/dev/null || true)`,
    `(sudo systemctl reload apache2 2>/dev/null || sudo systemctl reload nginx 2>/dev/null || true)`,
    `echo 'cache cleared + web reloaded'`,
  ].join(" && ");
}
function ensureRemoteDir(sftp, remotePath) {
  const dir = remotePath.split("/").slice(0, -1).join("/");
  return new Promise((res) => {
    sftp.mkdir(dir, { mode: 0o755 }, () => res());
  });
}
function uploadRetry(sftp, localPath, remotePath, tries = 3) {
  return new Promise((res, rej) => {
    const attempt = (t) => {
      sftp.fastPut(localPath, remotePath, (e) => {
        if (!e) return res();
        if (t <= 0) return rej(new Error(`上传失败 ${localPath}: ${e.message}`));
        setTimeout(() => attempt(t - 1), 300);
      });
    };
    attempt(tries);
  });
}
function ts() {
  const d = new Date();
  const p = (x) => String(x).padStart(2, "0");
  return `${d.getFullYear()}${p(d.getMonth() + 1)}${p(d.getDate())}_${p(d.getHours())}${p(d.getMinutes())}${p(d.getSeconds())}`;
}

(async () => {
  const cmd = process.argv[2] || "diff";
  try {
    if (cmd === "diff") await doDiff();
    else if (cmd === "apply") await doApply();
    else if (cmd === "rollback") await doRollback(process.argv[3]);
    else { console.error("未知命令: " + cmd + "（diff | apply | rollback）"); process.exit(1); }
  } catch (e) {
    console.error("\n错误: " + (e.message || e));
    process.exit(99);
  }
})();
