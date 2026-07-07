# 将GitHub仓库设置为私有的操作指南

## 概述

本文档提供了将GitHub仓库 `hicool-ml/workorder-system` 设置为私有的详细步骤。设置私有后，只有您自己可以访问和查看该仓库。

## 操作步骤

### 方法一：通过GitHub网页界面操作（推荐）

1. **登录GitHub**
   - 打开浏览器，访问 [https://github.com](https://github.com)
   - 使用您的账户 `hicool.ml@gmail.com` 登录

2. **导航到仓库页面**
   - 直接访问：[https://github.com/hicool-ml/workorder-system](https://github.com/hicool-ml/workorder-system)
   - 或者从您的GitHub主页进入仓库列表，点击 `workorder-system`

3. **进入仓库设置**
   - 在仓库页面的右上角，点击 "Settings" 选项卡
   - 滚动到页面底部找到 "Danger Zone" 区域

4. **更改仓库可见性**
   - 在 "Danger Zone" 中找到 "Change repository visibility" 选项
   - 点击 "Change visibility" 按钮

5. **选择私有设置**
   - 在弹出的对话框中，选择 "Make private"
   - 系统会显示更改可见性的影响说明

6. **确认更改**
   - 输入仓库名称 `workorder-system` 进行确认
   - 点击 "I understand, change repository visibility" 按钮

7. **完成设置**
   - 等待GitHub处理更改
   - 仓库页面会显示一个锁形图标，表示已设为私有

### 方法二：使用GitHub CLI（需要先安装）

如果您想使用命令行操作，需要先安装GitHub CLI：

1. **安装GitHub CLI**
   ```bash
   # Ubuntu/Debian
   sudo apt install gh
   
   # 或使用其他平台的安装方法
   # 参考：https://cli.github.com/manual/installation
   ```

2. **登录GitHub**
   ```bash
   gh auth login
   ```

3. **设置仓库为私有**
   ```bash
   cd /var/www/workorder
   gh repo edit hicool-ml/workorder-system --visibility private
   ```

## 设置私有后的影响

### ✅ 保留的内容
- 所有Git历史记录和版本标签
- 所有代码文件和文档
- Issues和Pull Requests（如果有的话）
- 仓库的设置和配置

### 🔄 发生的变化
- 公众无法再访问仓库
- 仓库不会出现在GitHub搜索结果中
- 只有您明确邀请的协作者才能访问
- 仓库URL保持不变，但未授权用户访问时会显示404

### ⚠️ 注意事项
- 如果有Fork此仓库的用户，他们的Fork仍然保持公开
- 之前公开的任何链接或引用将失效
- 如果需要团队协作，需要单独邀请每个成员

## 验证设置是否成功

1. **登录状态验证**
   - 在登录状态下访问仓库，应该能正常查看

2. **登出状态验证**
   - 打开无痕窗口或登出GitHub账户
   - 访问仓库页面，应该显示404错误

3. **检查仓库图标**
   - 在仓库列表中，私有仓库会显示锁形图标

## 团队协作（如需要）

如果需要邀请其他团队成员访问私有仓库：

1. 进入仓库的 "Settings" 页面
2. 在左侧菜单中选择 "Collaborators" 或 "Teams"
3. 点击 "Add people" 或 "Add team"
4. 输入用户名或团队名称
5. 设置适当的权限级别（Read、Write、Admin）
6. 发送邀请

## 安全建议

1. **定期审查协作者列表**
   - 移除不再需要访问权限的用户

2. **使用两步验证**
   - 为您的GitHub账户启用2FA

3. **监控访问日志**
   - 定期检查仓库的访问活动

4. **备份重要数据**
   - 虽然GitHub很可靠，但建议定期备份重要代码

## 恢复公开（如需要）

如果将来需要将仓库重新设为公开：

1. 进入仓库的 "Settings" 页面
2. 在 "Danger Zone" 中找到 "Change repository visibility"
3. 选择 "Make public"
4. 输入仓库名称确认
5. 点击确认按钮

## 联系支持

如果在设置过程中遇到问题：

1. **GitHub官方文档**
   - [https://docs.github.com](https://docs.github.com)

2. **GitHub支持**
   - [https://support.github.com](https://support.github.com)

---

**重要提醒**：设置私有后，请确保您有足够的备份，并妥善保管访问权限。此操作可以随时撤销，但请谨慎操作。

**操作日期**：2025-11-21  
**操作人**：hicool_ml  
**仓库**：hicool-ml/workorder-system