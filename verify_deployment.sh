#!/bin/bash

# Laravel工单系统部署验证脚本
# 用于验证部署是否成功

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  Laravel工单系统部署验证工具${NC}"
echo -e "${BLUE}========================================${NC}"

# 检查是否在项目根目录
if [ ! -f "artisan" ]; then
    echo -e "${RED}错误: 请在Laravel项目根目录下运行此脚本${NC}"
    exit 1
fi

echo -e "${GREEN}开始验证部署...${NC}"

# 验证计数器
PASSED=0
FAILED=0

# 验证函数
verify() {
    local test_name="$1"
    local test_command="$2"
    
    echo -n "测试 $test_name ... "
    
    if eval "$test_command" > /dev/null 2>&1; then
        echo -e "${GREEN}✓ 通过${NC}"
        ((PASSED++))
        return 0
    else
        echo -e "${RED}✗ 失败${NC}"
        ((FAILED++))
        return 1
    fi
}

echo -e "${YELLOW}1. 基础环境检查${NC}"

# 检查PHP版本
verify "PHP版本 >= 8.2" "php -r 'echo version_compare(PHP_VERSION, \"8.2.0\", \">=\") ? \"true\" : \"false\";' | grep -q 'true'"

# 检查必要的PHP扩展
REQUIRED_EXTENSIONS="mbstring pdo_mysql tokenizer xml ctype fileinfo json bcmath openssl"
for ext in $REQUIRED_EXTENSIONS; do
    verify "PHP扩展: $ext" "php -m | grep -q '$ext'"
done

# 检查Composer
verify "Composer安装" "command -v composer"

# 检查Node.js（可选）
verify "Node.js安装" "command -v node"

echo -e "${YELLOW}2. 项目文件检查${NC}"

# 检查关键文件
verify "artisan文件存在" "test -f artisan"
verify "composer.json存在" "test -f composer.json"
verify "package.json存在" "test -f package.json"
verify "环境配置文件" "test -f .env"

# 检查目录结构
verify "vendor目录" "test -d vendor"
verify "node_modules目录" "test -d node_modules"
verify "storage目录" "test -d storage"
verify "bootstrap/cache目录" "test -d bootstrap/cache"

echo -e "${YELLOW}3. 前端资源检查${NC}"

# 检查前端资源编译
verify "前端资源编译" "test -f public/build/manifest.json"
verify "CSS文件存在" "test -f public/build/assets/app-*.css"
verify "JS文件存在" "test -f public/build/assets/app-*.js"

echo -e "${YELLOW}4. 数据库连接检查${NC}"

# 检查数据库连接
if php artisan tinker --execute="DB::connection()->getPdo();" > /dev/null 2>&1; then
    echo -n "测试 数据库连接 ... "
    echo -e "${GREEN}✓ 通过${NC}"
    ((PASSED++))
    
    # 检查数据表
    echo -n "测试 数据表存在 ... "
    TABLE_COUNT=$(php artisan tinker --execute="DB::select('SHOW TABLES');" | grep -c "workorder\|users\|departments" || echo "0")
    if [ "$TABLE_COUNT" -gt 5 ]; then
        echo -e "${GREEN}✓ 通过 (发现 $TABLE_COUNT 个相关表)${NC}"
        ((PASSED++))
    else
        echo -e "${RED}✗ 失败 (数据表数量不足)${NC}"
        ((FAILED++))
    fi
else
    echo -n "测试 数据库连接 ... "
    echo -e "${RED}✗ 失败${NC}"
    ((FAILED++))
fi

echo -e "${YELLOW}5. Laravel应用检查${NC}"

# 检查应用密钥
verify "应用密钥设置" "php -r \"require 'vendor/autoload.php'; (new \Illuminate\Foundation\Application())->detectEnvironment(function() { return 'testing'; }); echo app('config')->get('app.key') !== null;\""

# 检查缓存配置
verify "配置缓存" "test -f bootstrap/cache/config.php"

# 检查路由缓存
verify "路由缓存" "test -f bootstrap/cache/routes-v7.php"

echo -e "${YELLOW}6. 权限检查${NC}"

# 检查目录权限
verify "storage目录权限" "test -w storage"
verify "bootstrap/cache权限" "test -w bootstrap/cache"

echo -e "${YELLOW}7. 功能测试${NC}"

# 测试Laravel命令
verify "Laravel命令行" "php artisan --version > /dev/null"

# 测试路由列表
verify "路由列表" "php artisan route:list > /dev/null"

echo ""
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  验证结果${NC}"
echo -e "${BLUE}========================================${NC}"
echo -e "${GREEN}通过: $PASSED${NC}"
echo -e "${RED}失败: $FAILED${NC}"
echo -e "${YELLOW}总计: $((PASSED + FAILED))${NC}"

if [ $FAILED -eq 0 ]; then
    echo ""
    echo -e "${GREEN}🎉 恭喜！部署验证完全通过！${NC}"
    echo ""
    echo -e "${BLUE}下一步操作:${NC}"
    echo -e "${BLUE}1. 启动开发服务器: php artisan serve --host=0.0.0.0 --port=8000${NC}"
    echo -e "${BLUE}2. 配置Web服务器指向 public 目录${NC}"
    echo -e "${BLUE}3. 访问应用程序进行功能测试${NC}"
    echo ""
    echo -e "${YELLOW}默认登录账户:${NC}"
    echo -e "${YELLOW}- 管理员: admin@workorder.com / admin123${NC}"
    echo -e "${YELLOW}- 工程师: engineer@workorder.com / engineer123${NC}"
    echo -e "${YELLOW}- 用户: user@workorder.com / user123${NC}"
    exit 0
else
    echo ""
    echo -e "${RED}❌ 验证失败，请检查上述错误项${NC}"
    echo ""
    echo -e "${YELLOW}常见问题解决:${NC}"
    echo -e "${YELLOW}1. 权限问题: sudo chown -R www-data:www-data storage bootstrap/cache${NC}"
    echo -e "${YELLOW}2. 数据库问题: 检查 .env 文件配置${NC}"
    echo -e "${YELLOW}3. 依赖问题: composer install && npm install${NC}"
    echo -e "${YELLOW}4. 缓存问题: php artisan config:clear && php artisan cache:clear${NC}"
    exit 1
fi