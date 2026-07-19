#!/usr/bin/env python3
"""
工单系统数据同步脚本
从生产服务器同步数据到本地 MySQL

凭证从环境变量读取，不再硬编码。请在运行前设置：
    export PROD_HOST=...   PROD_USER=...   PROD_PASS=...
    export MYSQL_USER=...  MYSQL_PASS=...  MYSQL_DB=...
    export MYSQL_BIN=...   # 本地 mysql 客户端路径（可选）

用法: python deploy/sync_data.py
"""
import paramiko
import subprocess
import sys
import os

PROD_HOST = os.environ.get("PROD_HOST", "")
PROD_USER = os.environ.get("PROD_USER", "cdu")
PROD_PASS = os.environ.get("PROD_PASS", "")
MYSQL_USER = os.environ.get("MYSQL_USER", "")
MYSQL_PASS = os.environ.get("MYSQL_PASS", "")
MYSQL_DB = os.environ.get("MYSQL_DB", "workorder_db")
MYSQL_BIN = os.environ.get("MYSQL_BIN", r"C:\mysql8\bin\mysql.exe")

for name, val in (("PROD_HOST", PROD_HOST), ("PROD_PASS", PROD_PASS),
                  ("MYSQL_USER", MYSQL_USER), ("MYSQL_PASS", MYSQL_PASS)):
    if not val:
        print(f"错误：请通过环境变量设置 {name}", file=sys.stderr)
        sys.exit(1)

def main():
    print("=" * 50)
    print("  数据同步: 生产 -> 本地")
    print("=" * 50)

    # 1. 连接生产服务器
    print("\n[1/3] 连接生产服务器...")
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    try:
        ssh.connect(PROD_HOST, username=PROD_USER, password=PROD_PASS, timeout=15)
        print("  连接成功")
    except Exception as e:
        print(f"  连接失败: {e}")
        sys.exit(1)

    # 2. 导出生产数据
    print("\n[2/3] 导出生产数据库...")
    cmd = (
        f"mysqldump -u {MYSQL_USER} -p'{MYSQL_PASS}' "
        f"--no-tablespaces --default-character-set=utf8mb4 "
        f"--routines --triggers {MYSQL_DB} 2>/dev/null"
    )
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=120)
    dump_data = stdout.read()
    ssh.close()

    dump_file = os.path.join(os.path.dirname(__file__), "production_sync_dump.sql")
    with open(dump_file, "wb") as f:
        f.write(dump_data)
    print(f"  导出完成: {len(dump_data) / 1024:.0f} KB")

    # 3. 导入到本地 MySQL
    print("\n[3/3] 导入到本地 MySQL...")
    result = subprocess.run(
        [MYSQL_BIN, "-u", MYSQL_USER, f"-p{MYSQL_PASS}", MYSQL_DB],
        input=dump_data,
        capture_output=True,
        timeout=120
    )
    os.remove(dump_file)

    if result.returncode != 0:
        error = result.stderr.decode("utf-8", errors="replace")
        # Ignore password warnings
        if "Warning" in error and "password" in error.lower():
            print("  导入成功 (有密码警告，可忽略)")
        else:
            print(f"  导入失败: {error[:300]}")
            sys.exit(1)
    else:
        print("  导入成功")

    # 验证
    verify = subprocess.run(
        [MYSQL_BIN, "-u", MYSQL_USER, f"-p{MYSQL_PASS}", MYSQL_DB, "-e",
         "SELECT "
         "(SELECT COUNT(*) FROM workorders) AS workorders, "
         "(SELECT COUNT(*) FROM users) AS users, "
         "(SELECT COUNT(*) FROM workorder_logs) AS logs, "
         "(SELECT COUNT(*) FROM notifications) AS notifications;"],
        capture_output=True,
        timeout=15
    )
    print("\n  数据验证:")
    print("  " + verify.stdout.decode("utf-8", errors="replace").replace("\t", " | ").strip())

    print("\n" + "=" * 50)
    print("  同步完成")
    print("=" * 50)

if __name__ == "__main__":
    main()