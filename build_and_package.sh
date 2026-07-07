#!/bin/bash

# 校园网工单系统 - 一键打包脚本
# 用于创建完整的部署包，包含环境配置、数据库和项目文件

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  校园网工单系统 - 一键打包工具${NC}"
echo -e "${BLUE}========================================${NC}"
echo -e "${YELLOW}目标系统: Ubuntu Server 24${NC}"
echo -e "${YELLOW}Web服务器: Apache2${NC}"
echo -e "${YELLOW}数据库: MySQL${NC}"
echo -e "${YELLOW}Web目录: /var/www/workorder${NC}"
echo -e "${YELLOW}数据库用户: cdu${NC}"
echo -e "${YELLOW}数据库密码: REDACTED_MYSQL_PASS${NC}"
echo -e "${YELLOW}数据库名: workorder_db${NC}"
echo ""

# 检查是否在项目根目录
if [ ! -f "composer.json" ]; then
    echo -e "${RED}错误: 请在项目根目录运行此脚本${NC}"
    exit 1
fi

# 检查必要的脚本文件
echo -e "${YELLOW}检查必要的脚本文件...${NC}"
REQUIRED_SCRIPTS=(
    "ubuntu_server_setup.sh"
    "export_workorder_database.sh"
    "create_deployment_package.sh"
)

for script in "${REQUIRED_SCRIPTS[@]}"; do
    if [ ! -f "$script" ]; then
        echo -e "${RED}错误: 缺少必要脚本文件: $script${NC}"
        exit 1
    fi
done

echo -e "${GREEN}✓ 所有必要脚本文件检查通过${NC}"

# 设置执行权限
echo -e "${YELLOW}设置脚本执行权限...${NC}"
chmod +x ubuntu_server_setup.sh
chmod +x export_workorder_database.sh
chmod +x create_deployment_package.sh
echo -e "${GREEN}✓ 脚本执行权限设置完成${NC}"

# 执行数据库导出
echo -e "${YELLOW}执行数据库导出...${NC}"
if ./export_workorder_database.sh; then
    echo -e "${GREEN}✓ 数据库导出成功${NC}"
else
    echo -e "${RED}错误: 数据库导出失败${NC}"
    exit 1
fi

# 执行项目打包
echo -e "${YELLOW}执行项目打包...${NC}"
if ./create_deployment_package.sh; then
    echo -e "${GREEN}✓ 项目打包成功${NC}"
else
    echo -e "${RED}错误: 项目打包失败${NC}"
    exit 1
fi

# 查找生成的部署包
LATEST_PACKAGE=$(ls -t campus-workorder-system-*.tar.gz 2>/dev/null | head -1)
if [ -z "$LATEST_PACKAGE" ]; then
    echo -e "${RED}错误: 未找到生成的部署包${NC}"
    exit 1
fi

# 查找对应的部署信息文件
INFO_FILE="${LATEST_PACKAGE%.tar.gz}_DEPLOYMENT_INFO.txt"
if [ ! -f "$INFO_FILE" ]; then
    echo -e "${YELLOW}警告: 未找到部署信息文件${NC}"
fi

# 获取文件信息
PACKAGE_SIZE=$(du -h "$LATEST_PACKAGE" | cut -f1)
PACKAGE_SHA256=$(sha256sum "$LATEST_PACKAGE" | cut -d' ' -f1)

# 显示最终结果
echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  打包完成！${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${YELLOW}生成的文件:${NC}"
echo -e "${GREEN}  部署包: ${LATEST_PACKAGE} (${PACKAGE_SIZE})${NC}"

if [ -f "$INFO_FILE" ]; then
    echo -e "${GREEN}  信息文件: ${INFO_FILE}${NC}"
fi

# 查找数据库文件
DB_FILE=$(ls -t workorder_database_*.sql.gz 2>/dev/null | head -1)
if [ -n "$DB_FILE" ]; then
    DB_SIZE=$(du -h "$DB_FILE" | cut -f1)
    echo -e "${GREEN}  数据库文件: ${DB_FILE} (${DB_SIZE})${NC}"
fi

echo ""
echo -e "${YELLOW}校验信息:${NC}"
echo -e "${GREEN}  部署包 SHA256: ${PACKAGE_SHA256}${NC}"

echo ""
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  部署说明${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""
echo -e "${YELLOW}1. 上传到服务器:${NC}"
echo -e "  使用scp、ftp或其他方式将 ${LATEST_PACKAGE} 上传到Ubuntu Server 24"
echo ""
echo -e "${YELLOW}2. 解压部署包:${NC}"
echo -e "  tar -xzf ${LATEST_PACKAGE}"
echo ""
echo -e "${YELLOW}3. 进入部署目录:${NC}"
echo -e "  cd $(basename "$LATEST_PACKAGE" .tar.gz)"
echo ""
echo -e "${YELLOW}4. 运行一键部署:${NC}"
echo -e "  sudo bash deploy_to_ubuntu.sh"
echo ""
echo -e "${YELLOW}5. 访问系统:${NC}"
echo -e "  系统地址: http://服务器IP地址"
echo ""
echo -e "${YELLOW}6. 默认登录:${NC}"
echo -e "  系统管理员: 用户名'admin' / 密码'admin123'"
echo -e "  工程师: 用户名'wangyang' / 密码'engineer123'"
echo -e "  普通用户: 用户名'(请查看数据库)' / 密码'user123'"
echo ""
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  系统配置信息${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""
echo -e "${YELLOW}数据库配置:${NC}"
echo -e "  主机: 127.0.0.1:3306"
echo -e "  数据库: workorder_db"
echo -e "  用户名: cdu"
echo -e "  密码: REDACTED_MYSQL_PASS"
echo ""
echo -e "${YELLOW}Web配置:${NC}"
echo -e "  目录: /var/www/workorder"
echo -e "  服务器: Apache2"
echo -e "  PHP版本: 8.3"
echo ""
echo -e "${YELLOW}注意事项:${NC}"
echo -e "  1. 确保目标服务器是Ubuntu Server 24"
echo -e "  2. 确保服务器有足够的磁盘空间"
echo -e "  3. 部署时需要root权限"
echo -e "  4. 首次登录后请修改默认密码"
echo -e "  5. 确保防火墙开放80端口"
echo -e "  6. 登录使用用户名，不是邮箱"
echo ""
echo -e "${GREEN}打包成功！可以将部署包上传到服务器进行部署。${NC}"

# 创建快速部署说明
QUICK_GUIDE="QUICK_DEPLOYMENT_GUIDE.txt"
cat > "$QUICK_GUIDE" << EOF
========================================
校园网工单系统 - 快速部署指南
========================================

1. 上传部署包到Ubuntu Server 24
   scp campus-workorder-system-*.tar.gz user@server:/tmp/

2. 登录服务器并解压
   ssh user@server
   cd /tmp
   tar -xzf campus-workorder-system-*.tar.gz
   cd campus-workorder-system-*

3. 运行一键部署
   sudo bash deploy_to_ubuntu.sh

4. 访问系统
   系统地址: http://服务器IP
   系统管理员: 用户名"系统管理员" / 密码"admin123"
   工程师: 用户名"测试工程师" / 密码"engineer123"
   普通用户: 用户名"测试用户" / 密码"user123"

========================================
部署包信息
========================================
文件名: ${LATEST_PACKAGE}
大小: ${PACKAGE_SIZE}
SHA256: ${PACKAGE_SHA256}
打包时间: $(date)

========================================
EOF

echo -e "${GREEN}✓ 已创建快速部署指南: ${QUICK_GUIDE}${NC}"