# 🔧 文件权限修复指南

## ❌ 问题描述
出现权限拒绝错误：
```
file_put_contents(.../vendor/composer/autoload_psr4.php): Failed to open stream: Permission denied
file_put_contents(.../.env): Failed to open stream: Permission denied
```

**原因**：文件所有者和权限设置不正确，当前用户无法写入项目文件。

## 🔍 诊断问题

### 1. 检查当前用户和权限
```bash
# 检查当前用户
whoami
id

# 检查项目文件权限
ls -la /home/waverjiang/campus-workorder-system-v1.0.0_*/

# 检查文件所有者
stat /home/waverjiang/campus-workorder-system-v1.0.0_*/
```

### 2. 检查具体文件权限
```bash
# 检查vendor目录
ls -la /home/waverjiang/campus-workorder-system-v1.0.0_*/vendor/

# 检查.env文件
ls -la /home/waverjiang/campus-workorder-system-v1.0.0_*/.env

# 检查当前目录权限
ls -la /home/waverjiang/
```

## 🔧 修复方案

### 方案1：修改文件所有者（推荐）
```bash
# 1. 将项目所有者改为当前用户
sudo chown -R waverjiang:waverjiang /home/waverjiang/campus-workorder-system-v1.0.0_*

# 2. 设置正确的目录权限
sudo chmod -R 755 /home/waverjiang/campus-workorder-system-v1.0.0_*

# 3. 设置文件权限
sudo find /home/waverjiang/campus-workorder-system-v1.0.0_* -type f -exec chmod 644 {} \;

# 4. 验证修复
ls -la /home/waverjiang/campus-workorder-system-v1.0.0_*/
```

### 方案2：使用sudo运行命令（临时解决）
```bash
# 1. 使用sudo运行Composer
sudo COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader

# 2. 使用sudo生成密钥
sudo php artisan key:generate

# 3. 设置正确的所有者
sudo chown -R waverjiang:waverjiang /home/waverjiang/campus-workorder-system-v1.0.0_*
```

### 方案3：重新解压并设置权限
```bash
# 1. 删除现有项目
sudo rm -rf /home/waverjiang/campus-workorder-system-v1.0.0_*

# 2. 重新解压
cd /home/waverjiang
tar -xzf campus-workorder-system-v1.0.0_*.tar.gz

# 3. 设置正确的所有者
sudo chown -R waverjiang:waverjiang campus-workorder-system-v1.0.0_*

# 4. 设置权限
chmod -R 755 campus-workorder-system-v1.0.0_*

# 5. 进入项目目录
cd campus-workorder-system-v1.0.0_*
```

## 🚀 完整的权限修复流程

### 步骤1：切换到正确的用户
```bash
# 确保使用waverjiang用户
sudo su - waverjiang
cd ~
```

### 步骤2：重新设置项目权限
```bash
# 1. 设置项目所有者
sudo chown -R waverjiang:waverjiang campus-workorder-system-v1.0.0_*

# 2. 设置目录权限
sudo find campus-workorder-system-v1.0.0_* -type d -exec chmod 755 {} \;

# 3. 设置文件权限
sudo find campus-workorder-system-v1.0.0_* -type f -exec chmod 644 {} \;

# 4. 设置特殊权限
sudo chmod +x campus-workorder-system-v1.0.0_*/artisan
sudo chmod +x campus-workorder-system-v1.0.0_*/*.sh
```

### 步骤3：验证权限
```bash
# 进入项目目录
cd campus-workorder-system-v1.0.0_*

# 测试写入权限
touch test_write.txt
rm test_write.txt

# 检查权限
ls -la
```

### 步骤4：重新运行部署命令
```bash
# 1. 设置Composer环境变量
export COMPOSER_ALLOW_SUPERUSER=1

# 2. 安装依赖
composer install --no-dev --optimize-autoloader

# 3. 生成应用密钥
php artisan key:generate

# 4. 修复.env配置
nano .env  # 修改数据库配置

# 5. 运行迁移
php artisan migrate --force
php artisan db:seed --force
```

## 🔍 权限验证命令

```bash
#!/bin/bash
echo "=== 权限检查脚本 ==="

PROJECT_DIR="/home/waverjiang/campus-workorder-system-v1.0.0_*"

echo "1. 当前用户信息："
whoami
id

echo -e "\n2. 项目目录权限："
ls -la $PROJECT_DIR

echo -e "\n3. 文件所有者："
sudo find $PROJECT_DIR -maxdepth 1 -exec ls -ld {} \;

echo -e "\n4. 关键文件权限："
echo "artisan文件："
ls -la $PROJECT_DIR/artisan
echo ".env文件："
ls -la $PROJECT_DIR/.env
echo "vendor目录："
ls -la $PROJECT_DIR/vendor/

echo -e "\n5. 写入权限测试："
cd $PROJECT_DIR
if touch test_permission.txt 2>/dev/null; then
    echo "✅ 写入权限正常"
    rm test_permission.txt
else
    echo "❌ 写入权限不足"
fi

echo -e "\n=== 检查完成 ==="
```

## 🆘 常见权限问题

### 1. "Permission denied" 错误
```bash
# 检查文件所有者
ls -la /home/waverjiang/campus-workorder-system-v1.0.0_*/

# 修改所有者
sudo chown -R waverjiang:waverjiang /home/waverjiang/campus-workorder-system-v1.0.0_*/

# 修改权限
sudo chmod -R 755 /home/waverjiang/campus-workorder-system-v1.0.0_*/
```

### 2. "Failed to open stream" 错误
```bash
# 检查目录权限
ls -ld /home/waverjiang/

# 确保用户有家目录权限
sudo chown waverjiang:waverjiang /home/waverjiang
sudo chmod 755 /home/waverjiang
```

### 3. Composer权限问题
```bash
# 方法1：使用正确的用户
sudo su - waverjiang
cd ~/campus-workorder-system-v1.0.0_*
export COMPOSER_ALLOW_SUPERUSER=1
composer install --no-dev --optimize-autoloader

# 方法2：修改Composer缓存权限
sudo chown -R waverjiang:waverjiang ~/.composer
sudo chmod -R 755 ~/.composer
```

## 📋 一键权限修复脚本

```bash
#!/bin/bash
echo "=== 一键权限修复脚本 ==="

# 1. 切换到waverjiang用户
if [ "$EUID" -eq 0 ]; then
    echo "切换到waverjiang用户..."
    exec sudo su - waverjiang "$0" "$@"
fi

# 2. 进入家目录
cd ~

# 3. 设置项目权限
echo "设置项目文件权限..."
sudo chown -R waverjiang:waverjiang campus-workorder-system-v1.0.0_*
sudo find campus-workorder-system-v1.0.0_* -type d -exec chmod 755 {} \;
sudo find campus-workorder-system-v1.0.0_* -type f -exec chmod 644 {} \;

# 4. 设置可执行权限
echo "设置可执行文件权限..."
sudo chmod +x campus-workorder-system-v1.0.0_*/artisan
find campus-workorder-system-v1.0.0_* -name "*.sh" -exec sudo chmod +x {} \;

# 5. 设置Composer缓存权限
echo "设置Composer缓存权限..."
sudo chown -R waverjiang:waverjiang ~/.composer 2>/dev/null || true
sudo chmod -R 755 ~/.composer 2>/dev/null || true

# 6. 进入项目目录
cd campus-workorder-system-v1.0.0_*

# 7. 测试权限
echo "测试文件权限..."
if touch test_write.txt 2>/dev/null; then
    echo "✅ 文件权限正常"
    rm test_write.txt
else
    echo "❌ 文件权限仍有问题"
    exit 1
fi

# 8. 运行部署命令
echo "开始部署..."
export COMPOSER_ALLOW_SUPERUSER=1
composer install --no-dev --optimize-autoloader
php artisan key:generate

echo "=== 权限修复完成 ==="
echo "现在可以继续配置数据库和运行迁移"
```

## 📞 获取帮助

如果权限问题仍然存在，请提供以下信息：

1. **用户信息**：
   ```bash
   whoami
   id
   ```

2. **文件权限**：
   ```bash
   ls -la /home/waverjiang/campus-workorder-system-v1.0.0_*/
   stat /home/waverjiang/campus-workorder-system-v1.0.0_*/
   ```

3. **错误详情**：
   ```bash
   运行命令的完整错误输出
   ```

---

**💡 提示**：确保始终使用waverjiang用户而不是root用户来运行项目命令，这样可以避免大多数权限问题。