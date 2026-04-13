# Sub-Store PHP 项目文档

> Advanced Subscription Manager for QX, Loon, Surge, Stash and Shadowrocket - PHP Version

## 项目概述

Sub-Store PHP 是原版 Sub-Store 的 PHP 重构版本，使用原生 PHP 8.0+ 和 SQLite 实现，保留了原版的所有核心功能。项目采用单入口架构、RESTful API 设计和 PSR-4 自动加载规范，适用于需要轻量级代理订阅管理的场景。

---

## 核心功能

- ✅ **订阅管理** - 添加、编辑、删除、下载远程/本地订阅
- ✅ **节点解析** - 支持多种代理协议（vmess、vless、ss、trojan、socks5 等）
- ✅ **格式转换** - 转换为目标平台格式（Clash、Surge、Shadowrocket 等）
- ✅ **集合管理** - 将多个订阅组合成一个集合
- ✅ **产物管理** - 将订阅转换为指定平台格式的产物
- ✅ **文件管理** - 托管和编辑配置文件
- ✅ **系统设置** - 配置系统参数（Gist Token、缓存时间等）
- ✅ **归档管理** - 备份和恢复订阅配置
- ✅ **Token 管理** - API 访问令牌管理
- ✅ **模块管理** - 脚本模块管理
- ✅ **同步功能** - 自动同步订阅和产物

---

## 目录结构详解

```
sub-store-php/
│
├── 📄 .env                      # 环境变量配置文件（数据库路径、调试模式等）
├──  .env.example              # 环境变量示例文件
├── 📄 .gitignore                # Git 忽略文件配置
├── 📄 .htaccess                 # Apache URL 重写配置
├── 📄 composer.json             # Composer 依赖管理配置
├── 📄 nginx.conf                # Nginx 配置文件（伪静态、PHP-FPM）
├── 📄 start.bat                 # Windows 一键启动脚本
│
├── 📁 public/                   # 【公开目录】Web 服务器指向此目录
│   ├── 📄 index.php             # 【入口文件】所有请求的统一入口
│   ├── 📄 index.html            # 【前端页面】主管理界面（SPA）
│   ├── 📄 debug.php             # 【调试工具】系统诊断和测试工具
│   ├── 📄 test-api.html         # 【API 测试】接口测试页面
│   ├── 📄 simple-test.php       # 【简单测试】基础功能测试
│   ├── 📁 assets/               # 【静态资源】
│   │   ├── 📁 css/
│   │   │   └── 📄 style.css     # 前端样式文件
│   │   └── 📁 js/
│   │       ├── 📄 api.js        # API 封装（RESTful 接口调用）
│   │       └── 📄 app.js        # 前端主逻辑（页面交互、数据渲染）
│   └── 📁 storage/              # 【上传目录】用户上传的文件存储
│
├── 📁 src/                      # 【源代码目录】后端核心代码
│   ├── 📄 bootstrap.php         # 【启动文件】初始化应用、加载配置、注册路由
│   ├── 📄 constants.php         # 【常量定义】全局常量（数据键名、存储路径）
│   │
│   ├── 📁 core/                 # 【核心层】框架核心功能
│   │   ├── 📄 App.php           # 【应用核心】单例模式，提供全局服务（数据读写、日志、HTTP）
│   │   ├── 📄 Router.php        # 【路由系统】HTTP 请求分发、路由匹配、中间件
│   │   └── 📁 proxy-utils/      # 【代理工具】节点解析、生成、处理
│   │       ├── 📄 ProxyParser.php      # 代理解析器（解析各种格式的订阅）
│   │       ├── 📄 ProxyProducer.php    # 代理生成器（生成目标平台格式）
│   │       └── 📄 ProxyUtils.php       # 代理工具类（去重、排序、过滤）
│   │
│   ├── 📁 restful/              # 【API 层】RESTful 路由处理器
│   │   ├── 📄 SubscriptionsRoute.php  # 订阅管理 API（增删改查、下载）
│   │   ├── 📄 CollectionsRoute.php    # 集合管理 API（组合多个订阅）
│   │   ├── 📄 ArtifactsRoute.php      # 产物管理 API（订阅转换产物）
│   │   ├── 📄 DownloadRoute.php       # 下载路由（/download、/share 路径）
│   │   ├── 📄 FileRoute.php           # 文件管理 API（文件增删改查）
│   │   ├── 📄 SettingsRoute.php       # 系统设置 API（全局配置）
│   │   ├── 📄 SyncRoute.php           # 同步 API（同步产物、订阅）
│   │   ├── 📄 TokenRoute.php          # Token 管理 API（访问令牌）
│   │   ├── 📄 ArchiveRoute.php        # 归档管理 API（备份恢复）
│   │   ├── 📄 ModuleRoute.php         # 模块管理 API（脚本模块）
│   │   └── 📄 errors.php              # 错误类定义（异常处理）
│   │
│   ├── 📁 utils/                # 【工具层】通用工具类
│   │   ├── 📄 Database.php      # 数据库操作（SQLite CRUD、事务）
│   │   ├── 📄 Download.php      # 下载工具（远程订阅抓取）
│   │   └──  Migration.php     # 数据迁移（版本升级）
│   │
│   └──  storage/              # 【内部存储】日志等内部数据
│       ├── 📁 logs/
│       │   └── 📄 app.log       # 应用日志文件
│       └── 📄 database.sqlite   # SQLite 数据库文件
│
├── 📁 storage/                  # 【数据存储】外部存储目录
│   ├── 📁 cache/                # 缓存文件目录
│   └── 📁 logs/                 # 日志文件目录
│
├── 📁 cron/                     # 【定时任务】Cron 脚本目录（待实现）
│
├── 📄 README.md                 # 项目说明（快速开始、特性）
├──  ARCHITECTURE.md           # 架构文档（设计模式、数据流）
├── 📄 PROGRESS.md               # 开发进度（已完成/待完成功能）
├──  QUICKSTART.md             # 快速入门指南
├──  TESTING.md                # 测试文档
└── 📄 DOCUMENTATION.md          # 本文档（完整文件说明）
```

---

## 文件功能详解

### 1️⃣ 根目录文件

| 文件 | 功能说明 | 核心作用 |
|------|---------|---------|
| **`.env`** | 环境变量配置 | 存储数据库路径、调试模式、应用密钥等敏感配置。不会提交到 Git。 |
| **`.env.example`** | 环境变量示例 | 提供配置模板，新用户复制后修改使用。 |
| **`.gitignore`** | Git 忽略规则 | 忽略敏感文件（.env）、数据库文件、日志文件、缓存文件等。 |
| **`composer.json`** | Composer 配置 | PHP 依赖管理，定义项目依赖和自动加载规则（PSR-4）。 |
| **`nginx.conf`** | Nginx 配置 | 伪静态规则、PHP-FPM 配置、静态资源缓存、安全设置。Windows 使用 TCP 端口 `127.0.0.1:9000`。 |
| **`start.bat`** | Windows 启动脚本 | 一键启动开发服务器，自动打开浏览器。适用于 Windows 环境。 |

---

### 2️⃣ public/ - 公开目录

> ⚠️ **重要**：Web 服务器（Nginx/Apache）的根目录应指向此目录，确保 `src/` 等敏感目录不被直接访问。

| 文件 | 功能说明 | 核心作用 |
|------|---------|---------|
| **`index.php`** | 应用入口文件 | 所有 HTTP 请求的统一入口，加载 bootstrap.php 并分发请求。包含全局错误捕获。 |
| **`index.html`** | 前端主页面 | SPA（单页应用）主界面，包含所有标签页（订阅、集合、产物、文件、设置）的 HTML 结构和模态框。 |
| **`debug.php`** | 系统诊断工具 | 提供系统信息查看、数据库测试、API 测试、文件权限检查等诊断功能。访问地址：`/debug.php` |
| **`test-api.html`** | API 测试页面 | 可视化 API 测试工具，可测试所有 RESTful 接口。 |
| **`simple-test.php`** | 简单测试脚本 | 快速测试 PHP 环境和基本功能。 |

#### public/assets/ - 静态资源

| 文件 | 功能说明 | 核心作用 |
|------|---------|---------|
| **`assets/css/style.css`** | 前端样式 | 定义页面布局、卡片样式、模态框、按钮、表格等 UI 组件样式。 |
| **`assets/js/api.js`** | API 封装层 | 封装所有 RESTful API 调用，提供统一接口（subscriptions、collections、artifacts 等）。 |
| **`assets/js/app.js`** | 前端主逻辑 | 页面交互、数据渲染、表单提交、模态框控制、Toast 提示、Loading 动画等。 |

---

### 3️⃣ src/ - 源代码目录

#### 3.1 启动文件

| 文件 | 功能说明 | 核心作用 |
|------|---------|---------|
| **`bootstrap.php`** | 应用启动文件 | ① 设置错误报告 ② 注册 PSR-4 自动加载器 ③ 加载常量 ④ 初始化 App 单例 ⑤ 运行数据迁移 ⑥ 创建 Router ⑦ 注册所有路由处理器。 |
| **`constants.php`** | 全局常量定义 | 定义数据存储键名（SUBS_KEY、COLLECTIONS_KEY 等）和存储路径常量。 |

#### 3.2 core/ - 核心层

| 文件 | 功能说明 | 核心作用 |
|------|---------|---------|
| **`App.php`** | 应用核心类（单例） | ① 全局数据读写（read/write） ② 日志记录（info/error/warning） ③ HTTP 请求（httpGet/httpPost） ④ 配置管理。使用单例模式，全局唯一实例。 |
| **`Router.php`** | 路由系统 | ① 路由注册（get/post/put/patch/delete） ② URL 解析和参数提取 ③ 中间件支持 ④ 请求分发。支持动态路由（如 `/api/sub/:name`）。 |

#### 3.3 core/proxy-utils/ - 代理工具

| 文件 | 功能说明 | 核心作用 |
|------|---------|---------|
| **`ProxyParser.php`** | 代理解析器 | 解析各种格式的订阅内容，支持：① Base64 编码 ② 单行代理 URI（vmess://、vless://、ss:// 等） ③ JSON 格式（Clash、Sing-box） ④ YAML 格式（Clash 配置文件） ⑤ 按行分割的纯文本。 |
| **`ProxyProducer.php`** | 代理生成器 | 将解析后的代理数据转换为目标平台格式，支持：① JSON ② Clash ③ Surge ④ Loon ⑤ QX ⑥ V2Ray ⑦ Sing-box 等平台。 |
| **`ProxyUtils.php`** | 代理工具类 | 提供代理节点处理功能：① 去重（deduplicate） ② 排序（sort） ③ 过滤（filter） ④ 重命名（rename）等。 |

#### 3.4 restful/ - API 层

所有路由文件遵循 PSR-4 规范，文件名与类名完全匹配（如 `SubscriptionsRoute.php` 对应 `SubscriptionsRoute` 类）。

| 文件 | 功能说明 | 核心路由 |
|------|---------|---------|
| **`SubscriptionsRoute.php`** | 订阅管理 | • GET `/api/subs` - 获取所有订阅<br>• POST `/api/subs` - 创建订阅（自动抓取+解析）<br>• GET `/api/sub/:name` - 获取订阅详情<br>• PATCH `/api/sub/:name` - 更新订阅<br>• DELETE `/api/sub/:name` - 删除订阅<br>• GET `/api/sub/flow/:name` - 获取流量信息 |
| **`CollectionsRoute.php`** | 集合管理 | • GET `/api/col` - 获取所有集合<br>• POST `/api/col` - 创建集合<br>• GET `/api/col/:name` - 获取集合详情<br>• PATCH `/api/col/:name` - 更新集合<br>• DELETE `/api/col/:name` - 删除集合 |
| **`ArtifactsRoute.php`** | 产物管理 | • GET `/api/artifact` - 获取所有产物<br>• POST `/api/artifact` - 创建产物<br>• GET `/api/artifact/:name` - 获取产物详情<br>• PATCH `/api/artifact/:name` - 更新产物<br>• DELETE `/api/artifact/:name` - 删除产物<br>• GET `/api/artifact/download/:name` - 下载产物 |
| **`DownloadRoute.php`** | 下载路由 | • GET `/download/:name` - 下载订阅<br>• GET `/download/collection/:name` - 下载组合订阅<br>• GET `/share/sub/:name` - 分享订阅链接<br>• GET `/share/col/:name` - 分享组合订阅链接 |
| **`FileRoute.php`** | 文件管理 | • GET `/api/file` - 获取所有文件<br>• POST `/api/file` - 创建文件<br>• GET `/api/file/*` - 获取文件详情<br>• PATCH `/api/file/*` - 更新文件<br>• DELETE `/api/file/*` - 删除文件 |
| **`SettingsRoute.php`** | 系统设置 | • GET `/api/settings` - 获取系统设置<br>• PATCH `/api/settings` - 更新系统设置 |
| **`SyncRoute.php`** | 同步 API | • POST `/api/sync` - 同步所有产物<br>• POST `/api/sync/artifact/:name` - 同步单个产物 |
| **`TokenRoute.php`** | Token 管理 | • GET `/api/token` - 获取所有 Token<br>• POST `/api/token` - 创建 Token<br>• DELETE `/api/token/:token` - 删除 Token |
| **`ArchiveRoute.php`** | 归档管理 | • GET `/api/archive` - 获取所有归档<br>• POST `/api/archive` - 创建归档<br>• DELETE `/api/archive/:id` - 删除归档 |
| **`ModuleRoute.php`** | 模块管理 | • GET `/api/module` - 获取所有模块<br>• POST `/api/module` - 创建模块<br>• GET `/api/module/:name` - 获取模块详情<br>• PATCH `/api/module/:name` - 更新模块<br>• DELETE `/api/module/:name` - 删除模块 |
| **`errors.php`** | 错误类定义 | 定义异常类：① BaseError（基础错误） ② InternalServerError（500） ③ ResourceNotFoundError（404） ④ RequestInvalidError（400） ⑤ NetworkError（503） |

#### 3.5 utils/ - 工具层

| 文件 | 功能说明 | 核心作用 |
|------|---------|---------|
| **`Database.php`** | 数据库操作类 | 封装 SQLite 操作：① 表初始化 ② CRUD 操作（selectOne/insert/update/delete） ③ 事务支持 ④ 预处理语句（防 SQL 注入）。 |
| **`Download.php`** | 下载工具类 | 远程订阅抓取：① HTTP 下载（支持重定向、超时、SSL） ② fetchSubscription（自动处理远程/本地） ③ fetchAndParse（下载并解析）。 |
| **`Migration.php`** | 数据迁移类 | 数据库版本升级：① 检测当前版本 ② 执行迁移脚本 ③ 数据兼容性处理。 |

---

### 4️⃣ storage/ - 数据存储

| 目录/文件 | 功能说明 |
|----------|---------|
| **`storage/database.sqlite`** | SQLite 数据库文件，存储所有订阅、集合、产物、设置等数据。采用键值对存储（settings 表）。 |
| **`storage/logs/`** | 日志目录，存储应用运行日志。 |
| **`storage/cache/`** | 缓存目录，存储临时缓存文件。 |
| **`public/storage/`** | 上传目录，存储用户上传的文件。 |

---

## 数据流程图

### 请求处理流程

```
用户请求
   ↓
[1] public/index.php (入口)
   ↓ 加载 bootstrap.php
[2] src/bootstrap.php (启动)
   ↓ 初始化 App、Router、注册路由
[3] src/core/Router.php (路由匹配)
   ↓ 解析 URL、提取参数、匹配路由
[4] src/restful/*Route.php (路由处理器)
   ↓ 执行业务逻辑
[5] src/core/App.php (应用核心)
   ↓ 数据读写
[6] src/utils/Database.php (数据库)
   ↓ SQLite 操作
[7] storage/database.sqlite (数据持久化)
   ↓ 返回结果
[8] Response (JSON/HTML/文件下载)
```

### 订阅创建流程

```
[1] 前端提交表单 (app.js)
   ↓ POST /api/subs
[2] SubscriptionsRoute::createSubscription()
   ↓ 验证名称、检查重复
[3] Download::fetchSubscription() (如果是远程订阅)
   ↓ HTTP 下载远程内容
[4] ProxyParser::parse()
   ↓ 解析代理节点
[5] 缓存节点数据到 sub['proxies']
   ↓ 保存到数据库
[6] App::write(SUBS_KEY, $allSubs)
   ↓ 写入 SQLite
[7] 返回成功响应
```

### 订阅下载流程

```
[1] 用户点击下载按钮
   ↓ GET /download/:name?target=clash
[2] DownloadRoute::downloadSubscription()
   ↓ 从数据库读取订阅
[3] 检查缓存
   ├─ 有 proxies 缓存 → 直接使用
   ├─ 有 content 缓存 → ProxyParser::parse() 解析
   └─ 无缓存 → Download::fetchSubscription() 重新抓取
[4] ProxyUtils::deduplicate() (去重)
   ↓ ProxyUtils::sort() (排序)
[5] ProxyProducer::produce($proxies, 'clash')
   ↓ 生成目标格式
[6] Response::text() (返回文件内容)
   ↓ 设置 Content-Type 和 Content-Disposition
[7] 浏览器下载文件
```

---

## 技术栈

| 技术 | 版本 | 用途 |
|------|------|------|
| **PHP** | >= 8.0 | 后端编程语言 |
| **SQLite** | 3.x | 轻量级数据库 |
| **PSR-4** | - | 自动加载规范 |
| **RESTful API** | - | API 设计风格 |
| **SPA** | - | 前端单页应用 |
| **Nginx** | - | Web 服务器（生产环境） |
| **PHP-FPM** | - | FastCGI 进程管理器 |

---

## 环境要求

- **PHP** >= 8.0
- **扩展**：
  - `pdo_sqlite` - SQLite 数据库支持
  - `curl` - HTTP 请求
  - `mbstring` - 多字节字符串处理
  - `json` - JSON 编码/解码
  - `openssl` - SSL/TLS 支持

---

## 快速开始

### 1. 安装依赖

```bash
cd sub-store-php
composer install
```

### 2. 配置环境变量

```bash
cp .env.example .env
# 编辑 .env 文件，配置数据库路径等
```

### 3. 启动开发服务器

```bash
# Windows
start.bat

# Linux/Mac
php -S localhost:3000 -t public
```

### 4. 访问应用

- **管理界面**：http://localhost:3000
- **诊断工具**：http://localhost:3000/debug.php
- **API 测试**：http://localhost:3000/api/test
- **健康检查**：http://localhost:3000/health

---

## Nginx 配置（生产环境）

```nginx
server {
    listen 80;
    server_name your-domain.com;
    
    root /path/to/sub-store-php/public;
    index index.php index.html;
    
    # API 请求处理
    location /api {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # 下载请求处理
    location /download {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # 分享链接处理
    location /share {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # PHP 处理（Windows 使用 TCP 端口）
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_pass 127.0.0.1:9000;  # Windows
        # fastcgi_pass unix:/tmp/php-cgi.sock;  # Linux
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # 静态资源缓存
    location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

---

## 常见问题

### 1. 所有 API 返回 500 错误

**原因**：PHP 环境禁用了 `putenv()` 和 `getenv()` 函数。

**解决**：项目已修复，改用 `$_ENV` 和 `$_SERVER` 替代。

### 2. 下载订阅返回 404

**原因**：Nginx 未配置 `/download` 路径的伪静态规则。

**解决**：添加 `location /download` 配置块，使用 `try_files` 转发到 `index.php`。

### 3. 创建订阅后看不到节点数量

**原因**：旧版本未自动解析节点。

**解决**：删除旧订阅，重新添加。新版本会自动解析并缓存节点。

### 4. Windows 下 Nginx 报错 "unix domain sockets are not supported"

**原因**：Windows 不支持 Unix socket。

**解决**：将 `fastcgi_pass unix:/tmp/php-cgi.sock;` 改为 `fastcgi_pass 127.0.0.1:9000;`。

---

## 开发规范

### 文件命名

- **类文件**：文件名必须与类名完全匹配（PSR-4）
  - ✅ `SubscriptionsRoute.php` → `class SubscriptionsRoute`
  - ❌ `subscriptions.php` → `class SubscriptionsRoute`

### 路由注册顺序

- **具体路由优先**：`/download/collection/:name` 必须在 `/download/:name` 之前注册
- 否则通用路由会拦截具体路由

### 日志输出

- **禁止使用 `echo`**：会破坏 JSON 响应格式
- **使用 `error_log()`**：写入服务器日志文件

### 错误处理

- 所有异常必须被捕获并返回标准 JSON 格式
- 使用 `errors.php` 中定义的错误类

---

## 许可证

本项目遵循 GPL V3 许可证

---

## 致谢

- **原 Sub-Store 项目**：https://github.com/sub-store-org/Sub-Store
- **resource-parser**：@KOP-XIAO
- **图标设计**：@Orz-3 和 @58xinian

---

## 贡献

欢迎提交 Issue 和 Pull Request！

---

**文档版本**：v1.0  
**最后更新**：2026-04-12  
**维护者**：Sub-Store PHP 开发团队
