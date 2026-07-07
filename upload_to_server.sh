#!/bin/bash

# Laravel工单系统自动上传脚本

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  Laravel工单系统自动上传工具${NC}"
echo -e "${BLUE}========================================${NC}"

# 默认配置
LOCAL_FILE="/var/www/workorder/packages/workorder-system_v20251121_123548.tar.gz"
REMOTE_USER="user"
REMOTE_HOST="your-server-ip"
REMOTE_PATH="/home/user"

# 检查是否提供了参数
if [ $# -eq 3 ]; then
    REMOTE_USER="$1"
    REMOTE_HOST="$2"
    REMOTE_PATH="$3"
elif [ $# -gt 0 ]; then
    echo -e "${YELLOW}用法: $0 [用户名] [服务器IP] [目标路径]${NC}"
    echo -e "${YELLOW}示例: $0 admin 192.168.1.100 /home/admin${NC}"
    echo -e "${YELLOW}或直接运行使用默认配置${NC}"
    echo ""
    read -p "是否继续使用默认配置? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 0
    fi
fi

# 显示配置信息
echo -e "${YELLOW}上传配置:${NC}"
echo -e "${YELLOW}本地文件: ${LOCAL_FILE}${NC}"
echo -e "${YELLOW}目标服务器: ${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_PATH}${NC}"
echo ""

# 检查本地文件是否存在
if [ ! -f "$LOCAL_FILE" ]; then
    echo -e "${RED}错误: 本地文件不存在: $LOCAL_FILE${NC}"
    
    # 尝试找到最新的打包文件
    echo -e "${YELLOW}尝试查找最新的打包文件...${NC}"
    LATEST_FILE=$(ls -t /var/www/workorder/packages/workorder-system_v*.tar.gz 2>/dev/null | head -1)
    
    if [ -n "$LATEST_FILE" ]; then
        echo -e "${GREEN}找到最新文件: $LATEST_FILE${NC}"
        read -p "是否使用此文件? (Y/n): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Nn]$ ]]; then
            exit 1
        fi
        LOCAL_FILE="$LATEST_FILE"
    else
        echo -e "${RED}错误: 未找到任何打包文件${NC}"
        echo -e "${YELLOW}请先运行: ./package_project.sh${NC}"
        exit 1
    fi
fi

# 显示文件信息
FILE_SIZE=$(du -h "$LOCAL_FILE" | cut -f1)
echo -e "${GREEN}文件信息:${NC}"
echo -e "${GREEN}文件名: $(basename "$LOCAL_FILE")${NC}"
echo -e "${GREEN}文件大小: $FILE_SIZE${NC}"
echo -e "${GREEN}修改时间: $(stat -c %y "$LOCAL_FILE")${NC}"
echo ""

# 确认上传
read -p "确认上传此文件? (Y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Nn]$ ]]; then
    echo -e "${YELLOW}上传已取消${NC}"
    exit 0
fi

# 测试连接
echo -e "${YELLOW}测试服务器连接...${NC}"
if ! ssh -o ConnectTimeout=10 -o BatchMode=yes "$REMOTE_USER@$REMOTE_HOST" "echo '连接成功'" 2>/dev/null; then
    echo -e "${RED}错误: 无法连接到服务器 $REMOTE_USER@$REMOTE_HOST${NC}"
    echo -e "${YELLOW}请检查:${NC}"
    echo -e "${YELLOW}1. 服务器IP地址是否正确${NC}"
    echo -e "${YELLOW}2. SSH服务是否运行${NC}"
    echo -e "${YELLOW}3. SSH密钥是否配置正确${NC}"
    echo -e "${YELLOW}4. 网络连接是否正常${NC}"
    exit 1
fi
echo -e "${GREEN}服务器连接正常${NC}"

# 选择上传方法
echo -e "${YELLOW}选择上传方法:${NC}"
echo "1) SCP (简单快速)"
echo "2) RSYNC (支持断点续传)"
echo "3) SFTP (交互式)"
read -p "请选择 (1-3): " -n 1 -r
echo

case $REPLY in
    1)
        UPLOAD_METHOD="scp"
        ;;
    2)
        UPLOAD_METHOD="rsync"
        ;;
    3)
        UPLOAD_METHOD="sftp"
        ;;
    *)
        echo -e "${YELLOW}使用默认方法: SCP${NC}"
        UPLOAD_METHOD="scp"
        ;;
esac

# 执行上传
echo -e "${GREEN}开始上传...${NC}"
START_TIME=$(date +%s)

case $UPLOAD_METHOD in
    "scp")
        echo -e "${YELLOW}使用SCP上传...${NC}"
        scp "$LOCAL_FILE" "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/"
        ;;
    "rsync")
        echo -e "${YELLOW}使用RSYNC上传（支持断点续传）...${NC}"
        rsync -avz --progress "$LOCAL_FILE" "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/"
        ;;
    "sftp")
        echo -e "${YELLOW}使用SFTP上传...${NC}"
        echo "put \"$LOCAL_FILE\" \"$REMOTE_PATH/\"" | sftp "$REMOTE_USER@$REMOTE_HOST"
        ;;
esac

END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))

# 验证上传结果
echo -e "${YELLOW}验证上传结果...${NC}"
REMOTE_FILE="$REMOTE_PATH/$(basename "$LOCAL_FILE")"

if ssh "$REMOTE_USER@$REMOTE_HOST" "test -f \"$REMOTE_FILE\"" 2>/dev/null; then
    # 检查文件大小
    LOCAL_SIZE=$(stat -c%s "$LOCAL_FILE")
    REMOTE_SIZE=$(ssh "$REMOTE_USER@$REMOTE_HOST" "stat -c%s \"$REMOTE_FILE\"" 2>/dev/null)
    
    if [ "$LOCAL_SIZE" -eq "$REMOTE_SIZE" ]; then
        echo -e "${GREEN}✓ 上传成功！${NC}"
        echo -e "${GREEN}✓ 文件大小匹配: $(du -h "$LOCAL_FILE" | cut -f1)${NC}"
        echo -e "${GREEN}✓ 上传时间: ${DURATION}秒${NC}"
        
        echo ""
        echo -e "${BLUE}========================================${NC}"
        echo -e "${GREEN}  下一步操作${NC}"
        echo -e "${BLUE}========================================${NC}"
        echo -e "${YELLOW}1. 登录服务器:${NC}"
        echo -e "${BLUE}   ssh $REMOTE_USER@$REMOTE_HOST${NC}"
        echo ""
        echo -e "${YELLOW}2. 进入目标目录:${NC}"
        echo -e "${BLUE}   cd $REMOTE_PATH${NC}"
        echo ""
        echo -e "${YELLOW}3. 解压文件:${NC}"
        echo -e "${BLUE}   tar -xzf $(basename "$LOCAL_FILE")${NC}"
        echo ""
        echo -e "${YELLOW}4. 进入项目目录:${NC}"
        echo -e "${BLUE}   cd $(basename "$LOCAL_FILE" .tar.gz)${NC}"
        echo ""
        echo -e "${YELLOW}5. 运行部署脚本:${NC}"
        echo -e "${BLUE}   ./auto_deploy.sh -e production -v${NC}"
        echo ""
        echo -e "${YELLOW}6. 验证部署:${NC}"
        echo -e "${BLUE}   ./verify_deployment.sh${NC}"
        echo ""
        echo -e "${GREEN}🎉 文件上传完成，准备部署！${NC}"
    else
        echo -e "${RED}✗ 上传失败：文件大小不匹配${NC}"
        echo -e "${RED}本地大小: $LOCAL_SIZE 字节${NC}"
        echo -e "${RED}远程大小: $REMOTE_SIZE 字节${NC}"
        exit 1
    fi
else
    echo -e "${RED}✗ 上传失败：远程文件不存在${NC}"
    exit 1
fi