# 备份 & 恢复

> 路径：侧边栏 → **设置** → **备份 & 恢复**
> 路由：`settings/backup`（仅管理员）

数据安全的最后一道防线。支持 Web 界面一键创建、上传、下载、删除、恢复备份，覆盖数据库与用户附件，无需登录服务器敲命令。

## 备份内容

每份备份是一个时间戳命名的目录，位于 `storage/app/private/backups/{YYYYMMDD_HHMMSS}/`，包含：

- `database.sql` — 数据库完整导出（优先用 `mysqldump`，不可用时回退纯 PHP 逐表导出）
- `attachments.zip` — `storage/app/public` 下的全部用户附件（故障图片、签名、PDF 等），打包时只保留相对路径，不泄露服务器目录结构

备份目录由 `php artisan backup:system` 命令生成，Web 界面的「立即备份」和自动调度都调用它。

## 操作

顶部工具栏三个按钮：

- **立即备份**：调用 `backup:system` 生成一份新备份，完成后刷新列表
- **上传备份**：选择一个 `.zip` 备份文件上传（最大 200MB）。系统会校验压缩包内必须含 `database.sql`，校验通过后解压到 `backups/uploaded/{时间戳_upload}/`，在列表中以「上传」徽标区分
- **刷新**：重新加载备份列表

备份列表每行展示：备份名称、创建时间、大小、内容徽标（`系统`/`上传` + `数据库`/`附件`），以及三个操作：

- **下载**：把整个备份目录打包成 `backup-{名称}.zip` 下载到本地
- **恢复**：仅含 `database.sql` 的备份才可恢复
- **删除**：永久删除该备份（不可撤销）

### 恢复流程

恢复是高风险操作，界面做了双重保护：

1. 点击「恢复」弹出确认框
2. 必须在输入框里手动输入「确认恢复」四个字，确认按钮才会启用

恢复执行时系统会：

1. **先自动备份当前状态**（调用 `backup:system`），作为安全网，万一恢复出错可立即回滚
2. 恢复数据库：优先 `mysql` 命令行导入，不可用时回退纯 PDO 按 `;` 切分逐条执行（自动跳过外键检查语句）
3. 恢复附件：解压 `attachments.zip` 到 `storage/app/public`，兼容新旧两种打包格式

恢复完成后建议刷新页面，让前端加载恢复后的数据。

## 自动备份

系统已在 `routes/console.php` 注册调度：**每日凌晨 2:00** 自动执行一次 `backup:system`，带 `->withoutOverlapping()` 防止任务重叠。

备份命令默认保留最近 **30 份**，超出部分按时间从旧到新自动清理。命令行手动执行时可用 `--keep=N` 调整保留份数：

```bash
php artisan backup:system --keep=14
```

### 让定时任务真正生效

Laravel 的调度只是在代码里"注册"了任务定义。要让它真正在凌晨 2 点触发，**必须有一个每分钟调用 `schedule:run` 的外部进程**。没有这个入口，自动备份永远不会执行——这是"没看到昨晚备份"最常见的原因。

不同部署方式下，这个入口的配置方法不同。下面按常见场景逐一说明。

#### Ubuntu / Debian 裸机（Apache 或 Nginx）

用部署账号（通常是 Web 目录属主）编辑 crontab：

`ash
crontab -e
`

加入一行（路径换成实际部署目录，本项目生产环境一般为 `/var/www/workorder`）：

`ash
* * * * * cd /var/www/workorder && php artisan schedule:run >> storage/logs/schedule.log 2>&1
`

- 若 `php` 不在 cron 的 PATH 里，用绝对路径（`which php` 查询，如 `/usr/bin/php`）
- 确认 cron 服务在运行：`sudo systemctl status cron`（Ubuntu/Debian）
- 若用 `www-data` 跑 cron，确保它对 `storage/` 有写权限

#### CentOS / RHEL / Rocky 裸机

步骤同上，只是 cron 服务名不同：

`ash
sudo systemctl status crond      # 确认在运行
sudo systemctl enable crond
crontab -e                       # 同样加那行 * * * * * ... schedule:run
`

#### Docker 部署

`Dockerfile` 的 supervisor 已内置一个 `scheduler` 进程，每分钟自动执行 `schedule:run`，**开箱即用，无需额外配置**。

若用的是 2026-07-20 之前的旧镜像（不含 scheduler 进程），需重新构建并启动：

`ash
docker compose up -d --build
`

不想重建镜像也可临时用 cron sidecar 容器，但推荐重建以获得内置调度。

#### Windows 本地 / IIS

Windows 没有 cron。开发环境一般不需要自动备份，直接在 Web 界面点「立即备份」即可。如确需自动触发，用「任务计划程序」创建一个任务：

- 触发器：每 1 分钟重复
- 操作：启动程序 `C:\path\to\php.exe`
- 参数：`artisan schedule:run`
- 起始位置：项目根目录（含 `artisan` 的目录）

注意：若生产跑在 Windows Server + IIS 上，同样用任务计划程序，务必让该任务以对 `storage/` 有写权限的账号运行。

#### 宝塔 / cPanel / 虚拟主机等面板环境

这类环境通常不允许直接编辑系统 crontab，但面板会提供「计划任务 / Cron Job」入口：

- **宝塔面板**：网站 → 计划任务 → 添加任务，类型选「Shell 脚本」，执行周期每 1 分钟，脚本内容：
  `ash
  cd /www/wwwroot/你的站点目录 && php artisan schedule:run
  `
- **cPanel**：Advanced → Cron jobs，Add New Cron Job，Common Settings 选 `* * * * *`，Command 填：
  `ash
  cd /home/用户名/public_html && php artisan schedule:run
  `
- **虚拟主机**：若不支持 CLI cron，自动备份无法生效，只能定期登录 Web 界面手动「立即备份」。

#### 排查"没有自动备份"

1. `php artisan schedule:list` —— 确认任务已注册，应看到 `0 2 * * * php artisan backup:system`
2. 确认 cron / scheduler / 计划任务确实在每分钟运行：看 `storage/logs/schedule.log` 是否每分钟新增一行
3. 凌晨 2 点过后查 `storage/app/private/backups/` 有无新目录；若仍无，手动跑 `php artisan backup:system` 看具体报错
4. 路径权限：执行调度的账号必须能写 `storage/app/private/backups/` 和 `storage/logs/`

> 历史背景：在 2026-07-20 之前，Docker 镜像未内置 scheduler 进程，文档也只给了一条通用 cron 而没区分具体部署场景，导致很多环境下自动备份实际未生效。现已为 Docker 镜像内置调度，并为各部署方式给出明确步骤。

## 命令行操作## 命令行操作

不依赖 Web 界面也能备份：

```bash
# 立即备份
php artisan backup:system

# 只保留最近 14 份
php artisan backup:system --keep=14
```

## 注意与排错

- **mysqldump / mysql 不可用**：命令会自动回退到纯 PHP 导出/导入，速度较慢但功能完整。Web 界面备份同样如此
- **恢复后附件丢失**：确认该备份的 `attachments.zip` 存在且完整；旧版备份可能因打包 bug 带有服务器绝对路径，新版已修复并兼容旧格式
- **备份占满磁盘**：自动清理只针对 `backups/` 目录下时间戳命名的备份，`backups/uploaded/` 下的手动上传备份不会被自动清理，需定期手动删除
- **Windows 下密码含特殊字符**：早期版本 `mysqldump -p` 的密码被双引号破坏，已修复，密码原样传递
- **大库恢复超时**：恢复接口已放宽 `set_time_limit(0)` 与 `memory_limit`，但若 PHP-FPM 有更短的超时配置仍可能中断，建议大库走命令行恢复
