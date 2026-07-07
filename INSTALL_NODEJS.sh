#!/bin/bash

# Node.js安装脚本
# 解决Laravel工单系统部署中的npm命令未找到问题

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  Node.js安装工具${NC}"
echo -e "${BLUE}========================================${NC}"

# 检测操作系统
if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS=$NAME
    VER=$VERSION_ID
else
    OS=$(uname -s)
    VER=$(uname -r)
fi
echo -e "${GREEN}检测到操作系统: $OS $VER${NC}"

# 检查Node.js是否已安装
if command -v node &> /dev/null; then
    NODE_VERSION=$(node --version)
    echo -e "${GREEN}Node.js已安装: $NODE_VERSION${NC}"
    
    # 检查npm是否可用
    if command -v npm &> /dev/null; then
        NPM_VERSION=$(npm --version)
        echo -e "${GREEN}npm已安装: $NPM_VERSION${NC}"
        echo -e "${GREEN}Node.js环境已就绪！${NC}"
        exit 0
    else
        echo -e "${RED}Node.js已安装但npm不可用${NC}"
        echo -e "${YELLOW}尝试重新安装Node.js...${NC}"
    fi
else
    echo -e "${YELLOW}Node.js未安装，开始安装...${NC}"
fi

# 安装Node.js
case $OS in
    "Ubuntu"* | "Debian"*)
        echo -e "${GREEN}使用NodeSource安装Node.js...${NC}"
        
        # 更新包管理器
        sudo apt-get update
        
        # 安装必要的依赖
        sudo apt-get install -y curl ca-certificates gnupg
        
        # 添加NodeSource GPG密钥
        curl -fsSL https://deb.nodesource.com/gpgkey/nodesource.gpg.key | sudo gpg --dearmor -o /usr/share/keyrings/nodesource.gpg
        
        # 添加NodeSource仓库
        echo "deb [signed-by=/usr/share/keyrings/nodesource.gpg] https://deb.nodesource.com/node_20.x nodistro main" | sudo tee /etc/apt/sources.list.d/nodesource.list
        
        # 更新包列表
        sudo apt-get update
        
        # 安装Node.js
        sudo apt-get install -y nodejs
        
        echo -e "${GREEN}Node.js安装完成${NC}"
        ;;
    "CentOS"* | "Red Hat"* | "Fedora"*)
        echo -e "${GREEN}使用NodeSource安装Node.js...${NC}"
        
        if command -v dnf &> /dev/null; then
            # 使用DNF
            sudo dnf install -y curl
            curl -fsSL https://rpm.nodesource.com/setup_20.x | sudo bash -
            sudo dnf install -y nodejs
        else
            # 使用YUM
            sudo yum install -y curl
            curl -fsSL https://rpm.nodesource.com/setup_20.x | sudo bash -
            sudo yum install -y nodejs
        fi
        
        echo -e "${GREEN}Node.js安装完成${NC}"
        ;;
    *)
        echo -e "${RED}不支持的操作系统: $OS${NC}"
        echo -e "${YELLOW}请手动安装Node.js: https://nodejs.org/${NC}"
        exit 1
        ;;
esac

# 验证安装
echo -e "${YELLOW}验证Node.js安装...${NC}"

if command -v node &> /dev/null; then
    NODE_VERSION=$(node --version)
    echo -e "${GREEN}✓ Node.js: $NODE_VERSION${NC}"
else
    echo -e "${RED}✗ Node.js安装失败${NC}"
    exit 1
fi

if command -v npm &> /dev/null; then
    NPM_VERSION=$(npm --version)
    echo -e "${GREEN}✓ npm: $NPM_VERSION${NC}"
else
    echo -e "${RED}✗ npm不可用${NC}"
    exit 1
fi

# 创建符号链接（如果需要）
if ! command -v npm &> /dev/null && command -v node &> /dev/null; then
    NODE_PATH=$(which node)
    NPM_PATH="$NODE_PATH/../lib/node_modules/npm/bin/npm"
    if [ -f "$NPM_PATH" ]; then
        echo -e "${YELLOW}创建npm符号链接...${NC}"
        sudo ln -sf "$NPM_PATH" /usr/local/bin/npm
    fi
fi

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  Node.js安装完成！${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${YELLOW}版本信息:${NC}"
echo -e "${BLUE}Node.js: $(node --version)${NC}"
echo -e "${BLUE}npm: $(npm --version)${NC}"
echo ""
echo -e "${YELLOW}下一步操作:${NC}"
echo -e "${BLUE}1. 重新运行部署脚本: ./auto_deploy.sh -e production -v${NC}"
echo -e "${BLUE}2. 或者手动安装前端依赖: npm install --production${NC}"
echo ""
echo -e "${GREEN}🎉 Node.js环境已就绪！${NC}"