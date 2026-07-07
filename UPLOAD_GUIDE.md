# Laravel工单系统上传指南

## 📋 上传前的准备工作

### 确认打包文件
```bash
# 在当前服务器上确认打包文件
cd /var/www/workorder
ls -lh packages/workorder-system_v*.tar.gz
```

应该看到类似输出：
```
-rw-rw-r-- 1 cdu cdu 4.5M Nov 21 12:35 packages/workorder-system_v20251121_123548.tar.gz
```

## 🚀 上传方法

### 方法1：使用SCP命令（推荐）

#### 基本语法
```bash
scp [选项] 源文件 用户名@目标服务器:目标路径
```

#### 具体示例
```bash
# 上传到目标服务器的/home/user目录
scp /var/www/workorder/packages/workorder-system_v20251121_123548.tar.gz user@target-server:/home/user/

# 上传到目标服务器的/var/www目录（需要sudo权限）
scp /var/www/workorder/packages/workorder-system_v20251121_123548.tar.gz user@target-server:/tmp/
```

#### 如果使用非标准SSH端口
```bash
scp -P 2222 /var/www/workorder/packages/workorder-system_v20251121_123548.tar.gz user@target-server:/home/user/
```

### 方法2：使用SFTP命令

#### 交互式上传
```bash
# 连接到目标服务器
sftp user@target-server

# 在sftp会话中
put /var/www/workorder/packages/workorder-system_v20251121_123548.tar.gz /home/user/
exit
```

#### 批量上传脚本
```bash
#!/bin/bash
# 创建上传脚本

LOCAL_FILE="/var/www/workorder/packages/workorder-system_v20251121_123548.tar.gz"
REMOTE_USER="user"
REMOTE_HOST="target-server"
REMOTE_PATH="/home/user"

# 使用sftp上传
echo "put $LOCAL_FILE $REMOTE_PATH" | sftp "$REMOTE_USER@$REMOTE_HOST"
```

### 方法3：使用RSYNC命令（推荐用于大文件）

#### 基本用法
```bash
rsync -avz /var/www/workorder/packages/workorder-system_v20251121_123548.tar.gz user@target-server:/home/user/
```

#### 带进度条的rsync
```bash
rsync -avz --progress /var/www/workorder/packages/workorder-system_v20251121_123548.tar.gz user@target-server:/home/user/
```

### 方法4：使用FileZilla（图形界面）

#### 步骤
1. **下载并安装FileZilla**
   ```bash
   # Ubuntu/Debian
   sudo apt install filezilla
   
   # 或访问官网下载: https://filezilla-project.org/
   ```

2. **配置连接**
   - 主机: target-server
   - 用户名: user
   - 密码: your_password
   - 端口: 22 (或您的SSH端口)
   - 协议: SFTP

3. **上传文件**
   - 左侧导航到本地文件: `/var/www/workorder/packages/`
   - 右侧导航到目标目录: `/home/user/`
   - 拖拽文件到右侧

### 方法5：使用wget/curl（从HTTP服务器）

#### 如果您有HTTP服务器
```bash
# 在当前服务器启动临时HTTP服务器
cd /var/www/workorder/packages
python3 -m http.server 8080

# 在目标服务器下载
wget http://current-server-ip:8080/workorder-system_v20251121_123548.tar.gz
```

## 🔧 上传后的验证

### 在目标服务器上验证上传
```bash
# 检查文件是否存在
ls -lh workorder-system_v20251121_123548.tar.gz

# 验证文件完整性
file workorder-system_v20251121_123548.tar.gz

# 应该显示: gzip compressed data
```

### 验证压缩包内容
```bash
# 查看压缩包内容（不解压）
tar -tzf workorder-system_v20251121_123548.tar.gz | head -10

# 应该看到类似输出:
# workorder-system_v20251121_123548/
# workorder-system_v20251121_123548/artisan
# workorder-system_v20251121_123548/composer.json
# ...
```

## 🚀 完整的上传和部署流程

### 步骤1：上传文件
```bash
# 使用SCP上传
scp /var/www/workorder/packages/workorder-system_v20251121_123548.tar.gz user@target-server:/home/user/
```

### 步骤2：登录目标服务器
```bash
ssh user@target-server
```

### 步骤3：解压和部署
```bash
# 进入上传目录
cd /home/user/

# 解压文件
tar -xzf workorder-system_v20251121_123548.tar.gz

# 进入项目目录
cd workorder-system_v20251121_123548/

# 运行部署脚本
./auto_deploy.sh -e production -v

# 验证部署
./verify_deployment.sh
```

## 🛠️ 常见问题和解决方案

### 问题1：权限被拒绝
```bash
# 解决方案：使用正确的用户和权限
scp /var/www/workorder/packages/workorder-system_v20251121_123548.tar.gz user@target-server:/tmp/
```

### 问题2：连接超时
```bash
# 解决方案：增加超时时间或使用rsync
scp -o ConnectTimeout=60 /var/www/workorder/packages/workorder-system_v20251121_123548.tar.gz user@target-server:/home/user/
```

### 问题3：磁盘空间不足
```bash
# 在目标服务器检查空间
df -h

# 如果空间不足，可以上传到临时目录
scp /var/www/workorder/packages/workorder-system_v20251121_123548.tar.gz user@target-server:/tmp/
```

### 问题4：网络中断
```bash
# 使用rsync支持断点续传
rsync -avz --partial --progress /var/www/workorder/packages/workorder-system_v20251121_123548.tar.gz user@target-server:/home/user/
```

## 📝 自动化上传脚本

### 创建自动化上传脚本
```bash
#!/bin/bash
# 文件名: upload_to_server.sh

# 配置变量
LOCAL_FILE="/var/www/workorder/packages/workorder-system_v20251121_123548.tar.gz"
REMOTE_USER="your_username"
REMOTE_HOST="your_server_ip"
REMOTE_PATH="/home/your_username"

# 颜色定义
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${YELLOW}开始上传文件到服务器...${NC}"

# 检查本地文件是否存在
if [ ! -f "$LOCAL_FILE" ]; then
    echo -e "${RED}错误: 本地文件不存在: $LOCAL_FILE${NC}"
    exit 1
fi

# 显示文件信息
echo -e "${GREEN}本地文件: $LOCAL_FILE${NC}"
echo -e "${GREEN}文件大小: $(du -h $LOCAL_FILE | cut -f1)${NC}"
echo -e "${GREEN}目标服务器: $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH${NC}"

# 使用rsync上传（支持断点续传和进度显示）
echo -e "${YELLOW}正在上传...${NC}"
rsync -avz --progress "$LOCAL_FILE" "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}上传成功！${NC}"
    echo -e "${YELLOW}下一步操作:${NC}"
    echo -e "${YELLOW}1. SSH登录: ssh $REMOTE_USER@$REMOTE_HOST${NC}"
    echo -e "${YELLOW}2. 解压: cd $REMOTE_PATH && tar -xzf $(basename $LOCAL_FILE)${NC}"
    echo -e "${YELLOW}3. 部署: cd $(basename $LOCAL_FILE .tar.gz) && ./auto_deploy.sh${NC}"
else
    echo -e "${RED}上传失败！${NC}"
    exit 1
fi
```

### 使用自动化脚本
```bash
# 保存脚本并设置执行权限
chmod +x upload_to_server.sh

# 运行脚本
./upload_to_server.sh
```

## 🎯 快速命令参考

### 最简单的上传命令
```bash
scp /var/www/workorder/packages/workorder-system_v20251121_123548.tar.gz user@server:/home/user/
```

### 完整的部署命令
```bash
# 1. 上传
scp /var/www/workorder/packages/workorder-system_v20251121_123548.tar.gz user@server:/home/user/

# 2. 登录
ssh user@server

# 3. 解压
cd /home/user
tar -xzf workorder-system_v20251121_123548.tar.gz
cd workorder-system_v20251121_123548/

# 4. 部署
./auto_deploy.sh -e production -v

# 5. 验证
./verify_deployment.sh
```

---

**🎉 现在您知道如何上传Laravel项目包到服务器了！**

选择最适合您的上传方法，推荐使用SCP或RSYNC命令。如果遇到问题，可以使用FileZilla图形界面工具。