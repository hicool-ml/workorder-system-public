# 🔍 Apache配置诊断和修复指南

## ❌ 问题分析
访问 `http://117.176.215.210:14580/` 仍然显示Apache2默认页面，说明配置没有生效。

## 🔍 诊断步骤

### 第1步：检查当前Apache配置
```bash
# 1. 检查启用的站点
sudo a2query -s

# 2. 检查Apache配置语法
sudo apache2ctl configtest

# 3. 检查监听的端口
sudo netstat -tlnp | grep :14580

# 4. 检查Apache进程
ps aux | grep apache2
```

### 第2步：检查Apache主配置
```bash
# 检查ports.conf中的端口配置
sudo cat /etc/apache2/ports.conf

# 检查主配置文件
sudo cat /etc/apache2/apache2.conf | grep -E "(Include|Ports)"
```

### 第3步：检查虚拟主机配置
```bash
# 列出所有可用的站点配置
ls -la /etc/apache2/sites-available/

# 列出所有启用的站点配置
ls -la /etc/apache2/sites-enabled/

# 检查当前工作配置的内容
sudo cat /etc/apache2/sites-enabled/000-default.conf
```

## 🔧 强制修复方案

### 方案1：完全重置Apache配置
```bash
# 1. 备份现有配置
sudo cp /etc/apache2/sites-available/000-default.conf /etc/apache2/sites-available/000-default.conf.backup

# 2. 删除所有启用的站点
sudo rm -f /etc/apache2/sites-enabled/*

# 3. 创建新的工单系统配置
sudo bash -c 'cat > /etc/apache2/sites-available/workorder.conf << EOF
<VirtualHost *:14580>
    ServerName 117.176.215.210
    DocumentRoot /home/waverjiang/campus-workorder-system-v1.0.0_20251121_183153/public
    
    <Directory /home/waverjiang/campus-workorder-system-v1.0.0_20251121_183153/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/workorder_error.log
    CustomLog \${APACHE_LOG_DIR}/workorder_access.log combined
</VirtualHost>
EOF'

# 4. 启用新配置
sudo a2ensite workorder.conf

# 5. 确保端口配置正确
sudo bash -c 'cat > /etc/apache2/ports.conf << EOF
Listen 80
Listen 14580
<IfModule ssl_module>
        Listen 443
</IfModule>
<IfModule mod_gnutls.c>
        Listen 443
</IfModule>
EOF'

# 6. 启用必要模块
sudo a2enmod rewrite
sudo a2enmod dir

# 7. 设置文件权限
sudo chown -R www-data:www-data /home/waverjiang/campus-workorder-system-v1.0.0_20251121_183153
sudo chmod -R 755 /home/waverjiang/campus-workorder-system-v1.0.0_20251121_183153

# 8. 测试配置
sudo apache2ctl configtest

# 9. 重启Apache（不是reload）
sudo systemctl restart apache2

# 10. 验证配置
sudo a2query -s
```

### 方案2：直接修改默认配置
```bash
# 1. 直接编辑默认配置文件
sudo nano /etc/apache2/sites-available/000-default.conf

# 2. 将内容替换为：
<VirtualHost *:14580>
    ServerName 117.176.215.210
    DocumentRoot /home/waverjiang/campus-workorder-system-v1.0.0_20251121_183153/public
    
    <Directory /home/waverjiang/campus-workorder-system-v1.0.0_20251121_183153/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/workorder_error.log
    CustomLog \${APACHE_LOG_DIR}/workorder_access.log combined
</VirtualHost>

# 3. 确保端口配置
sudo nano /etc/apache2/ports.conf
# 确保包含：Listen 14580

# 4. 重启Apache
sudo systemctl restart apache2
```

### 方案3：使用IP地址配置
```bash
# 创建基于IP的虚拟主机
sudo bash -c 'cat > /etc/apache2/sites-available/workorder.conf << EOF
<VirtualHost 117.176.215.210:14580>
    ServerName 117.176.215.210
    DocumentRoot /home/waverjiang/campus-workorder-system-v1.0.0_20251121_183153/public
    
    <Directory /home/waverjiang/campus-workorder-system-v1.0.0_20251121_183153/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/workorder_error.log
    CustomLog \${APACHE_LOG_DIR}/workorder_access.log combined
</VirtualHost>
EOF'

sudo a2ensite workorder.conf
sudo a2dissite 000-default.conf
sudo systemctl restart apache2
```

## 🔍 验证修复

### 检查配置是否生效
```bash
# 1. 检查启用的站点
sudo a2query -s

# 2. 检查端口监听
sudo netstat -tlnp | grep :14580

# 3. 检查Apache状态
sudo systemctl status apache2

# 4. 测试配置语法
sudo apache2ctl configtest

# 5. 检查虚拟主机配置
sudo apache2ctl -S
```

### 测试网站访问
```bash
# 1. 本地测试
curl -v http://127.0.0.1:14580/

# 2. 检查Apache日志
sudo tail -f /var/log/apache2/access.log
sudo tail -f /var/log/apache2/error.log

# 3. 检查工单系统日志
tail -f /home/waverjiang/campus-workorder-system-v1.0.0_20251121_183153/storage/logs/laravel.log
```

## 🚨 高级故障排除

### 检查SELinux（如果启用）
```bash
# 检查SELinux状态
sestatus

# 如果启用，设置正确的SELinux上下文
sudo semanage fcontext -a -t httpd_sys_content_t "/home/waverjiang/campus-workorder-system-v1.0.0_20251121_183153(/.*)?"
sudo restorecon -R /home/waverjiang/campus-workorder-system-v1.0.0_20251121_183153
```

### 检查AppArmor（如果启用）
```bash
# 检查AppArmor状态
sudo apparmor_status

# 如果有问题，可以暂时禁用（仅用于测试）
sudo systemctl stop apparmor
```

### 检查防火墙
```bash
# 检查UFW状态
sudo ufw status

# 检查iptables规则
sudo iptables -L -n

# 如果需要，开放端口14580
sudo ufw allow 14580/tcp
```

## 🎯 最终验证命令

运行以下命令进行完整验证：

```bash
#!/bin/bash
echo "=== Apache配置诊断脚本 ==="

echo "1. 检查启用的站点："
sudo a2query -s

echo -e "\n2. 检查端口监听："
sudo netstat -tlnp | grep :14580

echo -e "\n3. 检查Apache状态："
sudo systemctl status apache2 --no-pager

echo -e "\n4. 检查配置语法："
sudo apache2ctl configtest

echo -e "\n5. 检查虚拟主机配置："
sudo apache2ctl -S | grep 14580

echo -e "\n6. 检查文件权限："
ls -la /home/waverjiang/campus-workorder-system-v1.0.0_20251121_183153/public/

echo -e "\n7. 测试本地访问："
curl -I http://127.0.0.1:14580/ 2>/dev/null | head -1

echo -e "\n=== 诊断完成 ==="
```

## 📞 获取帮助

如果问题仍然存在，请提供以下信息：

1. **Apache配置输出**：
   ```bash
   sudo a2query -s
   sudo apache2ctl -S
   ```

2. **错误日志**：
   ```bash
   sudo tail -20 /var/log/apache2/error.log
   ```

3. **访问日志**：
   ```bash
   sudo tail -20 /var/log/apache2/access.log
   ```

4. **系统信息**：
   ```bash
   uname -a
   apache2 -v
   ```

---

**💡 提示**：运行完整的诊断脚本可以帮助快速定位问题所在。如果所有配置都正确但仍然显示默认页面，可能需要清除浏览器缓存或尝试使用不同的浏览器访问。