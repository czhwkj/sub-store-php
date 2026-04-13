# Sub-Store PHP 版本

Advanced Subscription Manager for QX, Loon, Surge, Stash and Shadowrocket - PHP Version

## 简介

这是 Sub-Store 的 PHP 重构版本,使用原生 PHP 8.0+ 和 SQLite 实现,保留了原项目的所有核心功能。

## 特性

- ✅ 订阅转换(支持多种代理协议和平台格式)
- ✅ 订阅格式化(过滤、排序、重命名等操作)
- ✅ 多订阅集合管理
- ✅ 文件托管和修改
- ✅ RESTful API 接口
- ✅ 默认中文界面
- 🚧 定时任务同步 (开发中)
- 🚧 Gist 备份还原 (开发中)

## 环境要求

- PHP >= 8.0
- SQLite3 扩展
- cURL 扩展
- mbstring 扩展
- JSON 扩展

## 快速开始

### 1. 安装依赖

```bash
composer install
```

### 2. 配置环境变量

复制 `.env.example` 为 `.env` 并修改配置:

```bash
cp .env.example .env
```

### 3. 启动开发服务器

```bash
php -S localhost:3000 -t public
```

### 4. 访问应用

打开浏览器访问: http://localhost:3000

测试 API: http://localhost:3000/api/test

## 目录结构

```
sub-store-php/
├── config/              # 配置文件
├── src/                 # 源代码
│   ├── core/           # 核心功能
│   ├── restful/        # API 路由
│   ├── utils/          # 工具类
│   ├── constants.php   # 常量定义
│   └── bootstrap.php   # 启动文件
├── public/             # 公开访问目录
│   └── index.php       # 入口文件
├── storage/            # 数据存储
│   ├── database.sqlite # SQLite 数据库
│   ├── cache/          # 缓存文件
│   └── logs/           # 日志文件
├── cron/               # 定时任务脚本
├── .env                # 环境变量
├── composer.json       # Composer 配置
└── README.md           # 项目说明
```

## API 文档

### 测试接口

```
GET /api/test
```

响应示例:
```json
{
  "success": true,
  "message": "Sub-Store PHP 版本运行正常",
  "version": "1.0.0"
}
```

### 健康检查

```
GET /health
```

响应示例:
```json
{
  "status": "ok",
  "timestamp": 1234567890
}
```

## 开发状态

当前项目处于**基础架构阶段**,已完成:

- ✅ 项目结构搭建
- ✅ 核心应用类 (App.php)
- ✅ 路由系统 (Router.php)
- ✅ 数据库层 (Database.php)
- ✅ 数据迁移 (Migration.php)
- ✅ 入口文件和启动流程

待完成:

- 🚧 代理解析器 (Parsers)
- 🚧 代理生成器 (Producers)
- 🚧 代理处理器 (Processors)
- 🚧 API 路由模块
- 🚧 高级功能 (Gist/定时任务/缓存等)
- 🚧 国际化完善

## 与原项目的区别

1. **语言**: 从 Node.js/JavaScript 改为 PHP 8.0+
2. **数据库**: 从内存/文件存储改为 SQLite
3. **运行模式**: PHP 请求-响应模式,定时任务通过系统 cron 实现
4. **默认语言**: 中文

## 许可证

本项目遵循 GPL V3 许可证

## 致谢

- 原 Sub-Store 项目: https://github.com/sub-store-org/Sub-Store
- @KOP-XIAO 的 resource-parser
- @Orz-3 和 @58xinian 的图标设计

## 贡献

欢迎提交 Issue 和 Pull Request!
