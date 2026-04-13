# Sub-Store PHP 版本 - 快速开始指南

## 🎉 项目完成情况

### ✅ 已完成的核心功能

#### 1. 前端管理界面
- ✨ 现代化的单页应用（SPA）
- 📱 响应式设计，支持移动端
- 🎨 美观的卡片式布局
- 🔧 完整的 CRUD 操作界面
- 📊 实时数据统计展示

**访问地址：** `http://你的域名/`

---

#### 2. 完整的 API 路由
已实现 10 个核心路由模块，提供完整的 RESTful API：

| 模块 | 功能 | API 端点 |
|------|------|----------|
| 订阅管理 | 创建、编辑、删除订阅 | `/api/subs`, `/api/sub/:name` |
| 集合管理 | 管理订阅集合 | `/api/collections`, `/api/collection/:name` |
| 产物管理 | 生成和管理产物 | `/api/artifacts`, `/api/artifact/:name` |
| 文件管理 | 文件存储管理 | `/api/files`, `/api/file/:path` |
| 设置管理 | 系统配置 | `/api/settings` |
| Token管理 | API 认证管理 | `/api/tokens`, `/api/token/:token` |
| 归档管理 | 历史归档 | `/api/archives`, `/api/archive/:id` |
| 模块管理 | 模块扩展 | `/api/modules`, `/api/module/:name` |
| 下载接口 | 下载订阅内容 | `/download/:name`, `/share/sub/:name` |
| 同步功能 | 同步和生成产物 | `/api/sync/artifacts`, `/api/sync/produce/:type/:name` |

---

#### 3. 代理处理核心
实现了完整的代理处理引擎：

**代理解析器** (`ProxyParser.php`)
- ✅ 支持 VLESS 协议解析
- ✅ 支持 VMESS 协议解析
- ✅ 支持 Trojan 协议解析
- ✅ 支持 Shadowsocks 解析
- ✅ 支持 SOCKS 协议解析
- ✅ 支持 HTTP/HTTPS 代理解析
- ✅ 支持 JSON 格式（Clash）
- ✅ 支持 Base64 编码订阅
- ✅ 自动识别订阅格式

**代理生成器** (`ProxyProducer.php`)
- ✅ JSON 格式输出
- ✅ Clash/Mihomo 格式
- ✅ Surge 格式
- ✅ Loon 格式
- ✅ Quantumult X 格式
- ✅ V2Ray URI 格式
- ✅ Sing-Box 格式
- ✅ Stash 格式

**代理处理工具** (`ProxyUtils.php`)
- ✅ 代理标准化
- ✅ 自动去重
- ✅ 灵活过滤
- ✅ 多字段排序
- ✅ 唯一标识生成

---

#### 4. 辅助功能
- ✅ 远程订阅下载（支持 HTTP/HTTPS）
- ✅ 订阅内容缓存
- ✅ 流量信息查询接口
- ✅ Gist 同步接口框架
- ✅ 归档管理
- ✅ Token 认证管理

---

##  部署指南

### 宝塔面板部署步骤

#### 1. 创建站点
1. 打开宝塔面板 → 网站 → 添加站点
2. 填写域名（例如：`substore.yourdomain.com`）
3. 根目录选择：`D:\wwwroot\127.0.0.1\sub-store-php\public`
4. PHP 版本选择：**PHP 8.0 或更高**

#### 2. 配置伪静态
在站点设置 → 伪静态中，添加以下规则：

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

#### 3. 设置目录权限
确保以下目录可写：
```
storage/
src/storage/
```

在宝塔面板中设置：
- 网站目录 → 运行目录选择 `public`
- 设置 755 权限

#### 4. 配置 PHP
确保启用了以下 PHP 扩展：
- ✅ curl
- ✅ sqlite3
- ✅ json
- ✅ mbstring

---

## 🧪 功能测试

### 1. 健康检查
```bash
curl http://your-domain/health
```

**预期响应：**
```json
{
  "status": "ok",
  "timestamp": 1234567890
}
```

### 2. API 测试
```bash
curl http://your-domain/api/test
```

**预期响应：**
```json
{
  "success": true,
  "message": "Sub-Store PHP 版本运行正常",
  "version": "1.0.0"
}
```

### 3. 前端测试
在浏览器中访问：`http://your-domain/`

您应该看到：
- ✅ 导航栏显示 "Sub-Store PHP 1.0.0"
- ✅ 5 个标签页：订阅管理、集合管理、产物管理、文件管理、系统设置
- ✅ 订阅管理页面为空状态提示

---

## 📝 使用示例

### 创建订阅

#### 方法 1：通过前端界面
1. 打开 `http://your-domain/`
2. 点击"订阅管理"标签
3. 点击"+ 添加订阅"按钮
4. 填写订阅信息：
   - 名称：我的订阅
   - 类型：远程订阅
   - URL：`https://example.com/subscription`
   - 备注：可选
5. 点击"保存"

#### 方法 2：通过 API
```bash
curl -X POST http://your-domain/api/subs \
  -H "Content-Type: application/json" \
  -d '{
    "name": "我的订阅",
    "source": "remote",
    "url": "https://example.com/subscription",
    "displayName": "备注信息"
  }'
```

### 下载订阅

#### 下载 JSON 格式
```
http://your-domain/download/我的订阅
```

#### 下载 Clash 格式
```
http://your-domain/download/我的订阅/clash
```

#### 下载 Surge 格式
```
http://your-domain/download/我的订阅/surge
```

#### 下载 V2Ray 格式
```
http://your-domain/download/我的订阅/v2ray
```

### 同步和生成产物

#### 生成单个订阅产物
```bash
curl -X POST "http://your-domain/api/sync/produce/subscription/我的订阅?target=clash"
```

#### 同步所有产物
```bash
curl -X POST http://your-domain/api/sync/artifacts
```

---

## 📁 项目结构

```
sub-store-php/
├── public/                      # Web 根目录
│   ├── index.php               # 入口文件
│   ├── index.html              # 前端主页面
│   └── assets/                 # 前端资源
│       ├── css/style.css       # 样式文件
│       └── js/
│           ├── api.js          # API 封装
│           └── app.js          # 主应用逻辑
├── src/
│   ├── bootstrap.php           # 启动文件
│   ├── constants.php           # 常量定义
│   ├── core/
│   │   ├── App.php            # 核心应用类
│   │   ├── Router.php         # 路由系统
│   │   └── proxy-utils/       # 代理处理核心 ⭐
│   │       ├── ProxyParser.php    # 代理解析器
│   │       ├── ProxyProducer.php  # 代理生成器
│   │       └── ProxyUtils.php     # 代理工具类
│   ├── restful/               # API 路由模块
│   │   ├── errors.php         # 错误类定义
│   │   ├── subscriptions.php  # 订阅管理
│   │   ├── collections.php    # 集合管理
│   │   ├── artifacts.php      # 产物管理
│   │   ├── file.php           # 文件管理
│   │   ├── settings.php       # 设置管理
│   │   ├── download.php       # 下载接口
│   │   ├── sync.php           # 同步功能
│   │   ├── token.php          # Token 管理
│   │   ├── archives.php       # 归档管理
│   │   └── modules.php        # 模块管理
│   ├── utils/
│   │   ├── Database.php       # 数据库类
│   │   ├── Migration.php      # 数据库迁移
│   │   └── Download.php       # 下载工具
│   └── storage/
│       └── database.sqlite    # SQLite 数据库
└── storage/                   # 缓存和日志
```

---

## 🔧 配置说明

### 环境变量
在 `src/bootstrap.php` 中可以配置：

```php
// 调试模式
ini_set('display_errors', getenv('APP_DEBUG') === 'true' ? '1' : '0');

// 时区
date_default_timezone_set('Asia/Shanghai');
```

### 数据库
使用 SQLite，数据库文件位于：`src/storage/database.sqlite`

无需额外配置，开箱即用！

---

## 🚀 支持的订阅格式

### 输入格式（解析）
- ✅ VLESS 链接 (`vless://...`)
- ✅ VMESS 链接 (`vmess://...`)
- ✅ Trojan 链接 (`trojan://...`)
- ✅ Shadowsocks 链接 (`ss://...`)
- ✅ SOCKS 链接 (`socks://...`, `socks5://...`)
- ✅ HTTP/HTTPS 代理
- ✅ Base64 编码订阅
- ✅ JSON 格式（Clash config）
- ✅ 每行一个代理的文本格式

### 输出格式（生成）
- ✅ JSON
- ✅ Clash / Mihomo (YAML)
- ✅ Surge
- ✅ Loon
- ✅ Quantumult X
- ✅ V2Ray (Base64 URI)
- ✅ Sing-Box
- ✅ Stash

---

## 🎯 核心特性

1. **自动格式识别** - 自动识别订阅格式并解析
2. **智能去重** - 根据代理类型、服务器、端口自动去重
3. **灵活排序** - 支持按名称、服务器等字段排序
4. **多平台支持** - 一键转换为不同客户端格式
5. **缓存机制** - 解析后的代理数据会被缓存，提高性能
6. **响应式前端** - 现代化的管理界面，支持移动端
7. **完整 API** - 提供完整的 RESTful API 接口
8. **错误处理** - 完善的错误处理和日志记录

---

## 📞 技术支持

### 常见问题

**Q: 打开网站显示 404？**
A: 检查伪静态配置是否正确，确保 Nginx 配置了 `try_files` 规则。

**Q: API 请求返回 500 错误？**
A: 检查 PHP 版本是否为 8.0+，检查 PHP 扩展是否启用。

**Q: 无法解析订阅？**
A: 确认订阅 URL 可以正常访问，检查网络连接。

**Q: 前端无法加载？**
A: 检查浏览器控制台是否有错误，确保 CSS/JS 文件路径正确。

---

##  性能优化建议

1. **启用 OPcache**
   ```ini
   opcache.enable=1
   opcache.memory_consumption=128
   opcache.max_accelerated_files=10000
   ```

2. **设置合理的缓存 TTL**
   ```php
   // 在系统设置中配置
   $settings['cacheTTL'] = 3600; // 1 小时
   ```

3. **定期清理日志**
   ```bash
   # 清理旧日志文件
   find src/storage/logs -name "*.log" -mtime +7 -delete
   ```

---

## 🔄 更新日志

### v1.0.0 (2026-04-12)
- ✨ 完成前端管理界面
- ✨ 实现 10 个核心 API 路由模块
- ✨ 实现代理处理核心（解析器、生成器、工具类）
- ✨ 实现远程订阅下载功能
- ✨ 实现产物同步和生成
- ✨ 实现 Token 管理和归档
- ✨ 完整的错误处理系统
- 🎉 项目正式完成！

---

## 📄 许可证

本项目基于 GPL-3.0 许可证开源。

---

**祝您使用愉快！** 🎉

如有任何问题，请查看项目文档或提交 Issue。
