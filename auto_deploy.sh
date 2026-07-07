#!/bin/bash

# Laravel工单系统自动化部署脚本
# 支持多种环境和配置选项

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# 默认配置
ENVIRONMENT="production"
CONFIG_FILE="deploy_config.json"
BACKUP_ENABLED=true
VERBOSE=false
SKIP_DEPENDENCIES=false
SKIP_DB_MIGRATION=false
SKIP_DB_SEED=false
FORCE_DEPLOY=false

# 显示帮助信息
show_help() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}  Laravel工单系统自动化部署脚本${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
    echo -e "${YELLOW}用法:${NC}"
    echo "  $0 [选项]"
    echo ""
    echo -e "${YELLOW}选项:${NC}"
    echo "  -e, --environment ENV     部署环境 (production|staging|development) [默认: production]"
    echo "  -c, --config FILE         配置文件路径 [默认: deploy_config.json]"
    echo "  -b, --backup              启用数据库备份 [默认: true]"
    echo "  --no-backup               禁用数据库备份"
    echo "  -v, --verbose             详细输出"
    echo "  --skip-dependencies       跳过依赖安装"
    echo "  --skip-db-migration       跳过数据库迁移"
    echo "  --skip-db-seed            跳过数据库种子数据"
    echo "  -f, --force               强制部署（跳过确认）"
    echo "  -h, --help                显示此帮助信息"
    echo ""
    echo -e "${YELLOW}示例:${NC}"
    echo "  $0 -e production -v                    # 生产环境部署，详细输出"
    echo "  $0 -e staging --skip-db-seed           # 测试环境部署，跳过种子数据"
    echo "  $0 --no-backup --force                  # 强制部署，不备份数据库"
    echo ""
}

# 解析命令行参数
parse_args() {
    while [[ $# -gt 0 ]]; do
        case $1 in
            -e|--environment)
                ENVIRONMENT="$2"
                shift 2
                ;;
            -c|--config)
                CONFIG_FILE="$2"
                shift 2
                ;;
            -b|--backup)
                BACKUP_ENABLED=true
                shift
                ;;
            --no-backup)
                BACKUP_ENABLED=false
                shift
                ;;
            -v|--verbose)
                VERBOSE=true
                shift
                ;;
            --skip-dependencies)
                SKIP_DEPENDENCIES=true
                shift
                ;;
            --skip-db-migration)
                SKIP_DB_MIGRATION=true
                shift
                ;;
            --skip-db-seed)
                SKIP_DB_SEED=true
                shift
                ;;
            -f|--force)
                FORCE_DEPLOY=true
                shift
                ;;
            -h|--help)
                show_help
                exit 0
                ;;
            *)
                echo -e "${RED}未知选项: $1${NC}"
                show_help
                exit 1
                ;;
        esac
    done
}

# 日志输出函数
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

log_debug() {
    if [ "$VERBOSE" = true ]; then
        echo -e "${PURPLE}[DEBUG]${NC} $1"
    fi
}

log_step() {
    echo -e "${CYAN}[STEP]${NC} $1"
}

# 检查系统要求
check_requirements() {
    log_step "检查系统要求..."
    
    # 检查PHP版本
    if ! command -v php &> /dev/null; then
        log_error "PHP未安装"
        echo -e "${YELLOW}请先安装PHP 8.2+：${NC}"
        echo -e "${YELLOW}Ubuntu/Debian:${NC}"
        echo -e "${YELLOW}  sudo apt update${NC}"
        echo -e "${YELLOW}  sudo apt install php8.3 php8.3-fpm php8.3-mysql php8.3-pgsql php8.3-mbstring php8.3-tokenizer php8.3-xml php8.3-ctype php8.3-fileinfo php8.3-json php8.3-bcmath php8.3-openssl php8.3-gd php8.3-curl php8.3-zip${NC}"
        echo -e "${YELLOW}CentOS/RHEL:${NC}"
        echo -e "${YELLOW}  sudo yum install php83 php83-php-fpm php83-php-mysqlnd php83-php-pgsql php83-php-mbstring php83-php-tokenizer php83-php-xml php83-php-ctype php83-php-fileinfo php83-php-json php83-php-bcmath php83-php-openssl php83-php-gd php83-php-curl php83-php-zip${NC}"
        echo -e "${YELLOW}或者使用包管理器：${NC}"
        echo -e "${YELLOW}  curl -sS https://getcomposer.org/installer | php${NC}"
        echo -e "${YELLOW}  sudo mv composer.phar /usr/local/bin/composer${NC}"
        exit 1
    fi
    
    PHP_VERSION=$(php -r "echo PHP_VERSION;")
    log_info "当前PHP版本: $PHP_VERSION"
    
    if ! php -r "version_compare(PHP_VERSION, '8.2.0', '>=') or exit(1);" ; then
        log_error "需要PHP 8.2或更高版本"
        exit 1
    fi
    
    # 检查Composer
    if ! command -v composer &> /dev/null; then
        log_error "Composer未安装"
        exit 1
    fi
    
    # 检查Node.js
    if ! command -v node &> /dev/null; then
        log_warn "Node.js未安装，将跳过前端资源编译"
    fi
    
    # 检查必要的PHP扩展
    REQUIRED_EXTENSIONS="mbstring pdo_mysql tokenizer xml ctype fileinfo json bcmath"
    for ext in $REQUIRED_EXTENSIONS; do
        if ! php -m | grep -q "$ext"; then
            log_error "缺少PHP扩展: $ext"
            exit 1
        fi
    done
    
    log_info "系统要求检查通过"
}

# 检查配置文件
check_config() {
    log_step "检查配置文件..."
    
    if [ ! -f "$CONFIG_FILE" ]; then
        log_error "配置文件不存在: $CONFIG_FILE"
        exit 1
    fi
    
    if ! command -v jq &> /dev/null; then
        log_warn "jq未安装，将无法解析JSON配置文件"
        return 1
    fi
    
    log_info "配置文件检查通过"
}

# 备份数据库
backup_database() {
    if [ "$BACKUP_ENABLED" = false ]; then
        log_info "跳过数据库备份"
        return 0
    fi
    
    log_step "备份数据库..."
    
    BACKUP_FILE="backup_$(date +%Y%m%d_%H%M%S).sql"
    
    if [ -f "export_database.sh" ]; then
        chmod +x export_database.sh
        ./export_database.sh "$BACKUP_FILE"
        log_info "数据库备份完成: $BACKUP_FILE"
    else
        log_warn "数据库备份脚本不存在，跳过备份"
    fi
}

# 安装依赖
install_dependencies() {
    if [ "$SKIP_DEPENDENCIES" = true ]; then
        log_info "跳过依赖安装"
        return 0
    fi
    
    log_step "安装PHP依赖..."
    composer install --no-dev --optimize-autoloader --no-interaction
    
    if command -v node &> /dev/null; then
        log_step "安装前端依赖..."
        npm install --production
        
        log_step "编译前端资源..."
        # 检查vite是否可用
        if [ -f "node_modules/.bin/vite" ]; then
            log_info "使用本地vite编译前端资源..."
            ./node_modules/.bin/vite build
        elif [ -f "node_modules/vite/bin/vite.js" ]; then
            log_info "使用vite脚本编译前端资源..."
            node node_modules/vite/bin/vite.js build
        else
            log_warn "vite未找到，跳过前端资源编译"
            log_info "可以稍后手动运行: npm run build"
        fi
    fi
    
    log_info "依赖安装完成"
}

# 配置环境
setup_environment() {
    log_step "配置环境..."
    
    # 复制环境配置文件
    if [ ! -f ".env" ]; then
        if [ -f ".env.example" ]; then
            cp .env.example .env
            log_info "已创建.env文件"
        else
            log_warn ".env.example文件不存在"
        fi
        
        # 生成应用密钥
        php artisan key:generate --force
        log_info "已生成应用密钥"
    else
        log_info ".env文件已存在"
    fi
    
    # 创建必要目录
    if command -v jq &> /dev/null && [ -f "$CONFIG_FILE" ]; then
        DIRECTORIES=$(jq -r '.deployment.directories_to_create[]' "$CONFIG_FILE" 2>/dev/null)
        for dir in $DIRECTORIES; do
            mkdir -p "$dir"
            log_debug "创建目录: $dir"
        done
    else
        # 默认目录
        mkdir -p storage/logs
        mkdir -p storage/framework/cache
        mkdir -p storage/framework/sessions
        mkdir -p storage/framework/views
        mkdir -p storage/app/public
        mkdir -p bootstrap/cache
    fi
    
    log_info "环境配置完成"
}

# 设置权限
set_permissions() {
    log_step "设置文件权限..."
    
    if command -v jq &> /dev/null && [ -f "$CONFIG_FILE" ]; then
        STORAGE_PERM=$(jq -r '.deployment.permissions.storage' "$CONFIG_FILE" 2>/dev/null)
        CACHE_PERM=$(jq -r '.deployment.permissions.bootstrap_cache' "$CONFIG_FILE" 2>/dev/null)
        
        chmod -R "${STORAGE_PERM:-775}" storage
        chmod -R "${CACHE_PERM:-775}" bootstrap/cache
    else
        chmod -R 775 storage bootstrap/cache
    fi
    
    log_info "权限设置完成"
}

# 数据库迁移
migrate_database() {
    if [ "$SKIP_DB_MIGRATION" = true ]; then
        log_info "跳过数据库迁移"
        return 0
    fi
    
    log_step "执行数据库迁移..."
    
    # 检查数据库连接
    if ! php artisan db:show 2>/dev/null; then
        log_error "数据库连接失败，请检查.env配置"
        exit 1
    fi
    
    # 首先运行迁移，创建数据库结构
    php artisan migrate --force
    log_info "数据库结构迁移完成"
    
    # 检查是否需要运行种子数据
    if [ "$SKIP_DB_SEED" = false ]; then
        log_step "导入种子数据..."
        php artisan db:seed --force
        log_info "种子数据导入完成"
    else
        log_info "跳过种子数据导入"
    fi
}

# 导入种子数据
seed_database() {
    if [ "$SKIP_DB_SEED" = true ]; then
        log_info "跳过数据库种子数据"
        return 0
    fi
    
    log_step "导入种子数据..."
    
    php artisan db:seed --force
    log_info "种子数据导入完成"
}

# 优化应用
optimize_application() {
    log_step "优化应用..."
    
    # 创建符号链接
    php artisan storage:link
    
    # 清理缓存
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    php artisan cache:clear
    
    # 优化缓存
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    log_info "应用优化完成"
}

# 健康检查
health_check() {
    log_step "执行健康检查..."
    
    # 检查Laravel应用状态
    if ! php artisan tinker --execute="echo 'OK';" 2>/dev/null | grep -q "OK"; then
        log_error "Laravel应用健康检查失败"
        return 1
    fi
    
    log_info "健康检查通过"
}

# 部署后操作
post_deployment() {
    log_step "执行部署后操作..."
    
    if command -v jq &> /dev/null && [ -f "$CONFIG_FILE" ]; then
        COMMANDS=$(jq -r '.post_deployment.commands[]' "$CONFIG_FILE" 2>/dev/null)
        for cmd in $COMMANDS; do
            log_info "执行: $cmd"
            eval "$cmd"
        done
    fi
    
    log_info "部署后操作完成"
}

# 显示部署摘要
show_summary() {
    echo ""
    echo -e "${BLUE}========================================${NC}"
    echo -e "${GREEN}  部署完成！${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo -e "${YELLOW}部署环境: ${ENVIRONMENT}${NC}"
    echo -e "${YELLOW}部署时间: $(date)${NC}"
    echo ""
    echo -e "${CYAN}默认登录账户:${NC}"
    echo -e "${CYAN}管理员: admin@workorder.com / admin123${NC}"
    echo -e "${CYAN}工程师: engineer@workorder.com / engineer123${NC}"
    echo -e "${CYAN}用户: user@workorder.com / user123${NC}"
    echo ""
    echo -e "${YELLOW}启动服务:${NC}"
    echo -e "${YELLOW}php artisan serve --host=0.0.0.0 --port=8000${NC}"
    echo ""
}

# 主函数
main() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}  Laravel工单系统自动化部署${NC}"
    echo -e "${BLUE}========================================${NC}"
    
    # 解析命令行参数
    parse_args "$@"
    
    # 显示配置信息
    log_info "部署环境: $ENVIRONMENT"
    log_info "配置文件: $CONFIG_FILE"
    log_info "数据库备份: $BACKUP_ENABLED"
    log_info "详细输出: $VERBOSE"
    
    # 确认部署
    if [ "$FORCE_DEPLOY" = false ]; then
        echo ""
        read -p "确认开始部署？(y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            log_info "部署已取消"
            exit 0
        fi
    fi
    
    # 执行部署步骤
    check_requirements
    check_config
    backup_database
    install_dependencies
    setup_environment
    set_permissions
    migrate_database
    seed_database
    optimize_application
    health_check
    post_deployment
    show_summary
}

# 运行主函数
main "$@"