# 测试指南

## 环境准备

### Windows 用户

1. 下载并安装 PHP 8.0+:
   - 访问: https://windows.php.net/download/
   - 下载 VS16 x64 Thread Safe 版本
   - 解压到 `C:\php`

2. 配置环境变量:
   - 右键"此电脑" -> 属性 -> 高级系统设置 -> 环境变量
   - 在"系统变量"中找到 `Path`,点击编辑
   - 添加 `C:\php` (根据你的实际安装路径)
   - 确定保存

3. 启用必要扩展:
   - 编辑 `C:\php\php.ini`
   - 确保以下行没有被注释(去掉前面的分号):
     ```ini
     extension=pdo_sqlite
     extension=curl
     extension=mbstring
     extension=json
     ```

4. 验证安装:
   ```bash
   php -v
   php -m | findstr sqlite
   php -m | findstr curl
   php -m | findstr mbstring
   ```

### Linux 用户

```bash
# Ubuntu/Debian
sudo apt install php8.0 php8.0-sqlite3 php8.0-curl php8.0-mbstring

# CentOS/RHEL
sudo yum install php80-php php80-php-sqlite3 php80-php-curl php80-php-mbstring
```

### macOS 用户

```bash
brew install php@8.0
```

## 安装依赖

```bash
cd d:\wangzhan\127.0.0.1\sub-store-php
composer install
```

## 启动开发服务器

```bash
php -S localhost:3000 -t public
```

## 测试 API

### 方法 1: 浏览器访问

打开浏览器访问:
- http://localhost:3000/api/test
- http://localhost:3000/health

### 方法 2: 使用 cURL

```bash
# 测试接口
curl http://localhost:3000/api/test

# 健康检查
curl http://localhost:3000/health
```

### 方法 3: 使用 PowerShell

```powershell
# 测试接口
Invoke-RestMethod -Uri http://localhost:3000/api/test

# 健康检查
Invoke-RestMethod -Uri http://localhost:3000/health
```

## 预期响应

### /api/test

```json
{
  "success": true,
  "message": "Sub-Store PHP 版本运行正常",
  "version": "1.0.0"
}
```

### /health

```json
{
  "status": "ok",
  "timestamp": 1234567890
}
```

## 常见问题

### 1. 提示 "php 不是内部或外部命令"

**解决方案**: PHP 未添加到系统环境变量,请参考上面的"环境准备"部分。

### 2. 提示缺少扩展

**解决方案**: 
- 编辑 `php.ini` 文件
- 找到对应的 `extension=xxx` 行
- 去掉行首的分号 `;`
- 重启 PHP 服务器

### 3. 数据库文件无法创建

**解决方案**:
- 确保 `storage` 目录存在
- 确保有写入权限
- Windows 下可能需要以管理员身份运行

### 4. 端口 3000 已被占用

**解决方案**: 使用其他端口
```bash
php -S localhost:8080 -t public
```

## 下一步

基础架构已完成,接下来将实现:
1. 代理解析器
2. 代理生成器
3. 代理处理器
4. API 路由模块
5. 高级功能

请查看 README.md 了解更多项目信息。
