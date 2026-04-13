# Sub-Store PHP 项目架构说明

## 核心架构

```
┌─────────────────────────────────────────┐
│          客户端 (浏览器/API)              │
└──────────────┬──────────────────────────┘
               │ HTTP 请求
               ▼
┌─────────────────────────────────────────┐
│         public/index.php (入口)          │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│       src/bootstrap.php (启动)           │
│  ┌───────────────────────────────────┐  │
│  │ 1. 加载自动加载器                  │  │
│  │ 2. 加载常量                        │  │
│  │ 3. 初始化 App                      │  │
│  │ 4. 运行 Migration                  │  │
│  │ 5. 创建 Router                     │  │
│  │ 6. 注册路由                        │  │
│  └───────────────────────────────────┘  │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│      src/core/Router.php (路由)         │
│  ┌───────────────────────────────────┐  │
│  │ 1. 解析请求方法和路径              │  │
│  │ 2. 匹配路由规则                    │  │
│  │ 3. 执行中间件                      │  │
│  │ 4. 调用处理器                      │  │
│  └───────────────────────────────────┘  │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│     路由处理器 (RESTful API)             │
│  ┌───────────────────────────────────┐  │
│  │ - Subscriptions                   │  │
│  │ - Collections                     │  │
│  │ - Download                        │  │
│  │ - Settings                        │  │
│  │ - ...                             │  │
│  └───────────────────────────────────┘  │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│      src/core/App.php (应用核心)        │
│  ┌───────────────────────────────────┐  │
│  │ - 数据读写                         │  │
│  │ - 日志记录                         │  │
│  │ - HTTP 请求                        │  │
│  └───────────────────────────────────┘  │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│   src/utils/Database.php (数据库)       │
│  ┌───────────────────────────────────┐  │
│  │ - SQLite 操作                      │  │
│  │ - CRUD 封装                        │  │
│  │ - 事务支持                         │  │
│  └───────────────────────────────────┘  │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│      storage/database.sqlite            │
│         (SQLite 数据库文件)              │
└─────────────────────────────────────────┘
```

## 数据流示例: 获取订阅列表

```
1. 用户请求: GET /api/subs
                ↓
2. Router 匹配路由: $router->get('/api/subs', ...)
                ↓
3. 执行处理器: Subscriptions::getAll($req, $res)
                ↓
4. 从 App 读取数据: $app->read(SUBS_KEY)
                ↓
5. Database 查询: SELECT * FROM settings WHERE key = 'subs'
                ↓
6. 返回 JSON 响应: $res->json(['success' => true, 'data' => ...])
```

## 类关系图

```
┌──────────────────────┐
│       App            │ ◄── 单例,全局访问点
│──────────────────────│
│ - db: Database       │
│ - config: array      │
│ - logFile: string    │
│──────────────────────│
│ + read()             │
│ + write()            │
│ + info()             │
│ + error()            │
│ + httpGet()          │
│ + httpPost()         │
└──────────┬───────────┘
           │ 使用
           ▼
┌──────────────────────┐
│     Database         │ ◄── SQLite 封装
│──────────────────────│
│ - pdo: PDO           │
│ - dbPath: string     │
│──────────────────────│
│ + selectOne()        │
│ + selectAll()        │
│ + insert()           │
│ + update()           │
│ + delete()           │
│ + transaction()      │
└──────────────────────┘

┌──────────────────────┐
│      Router          │ ◄── 路由系统
│──────────────────────│
│ - routes: array      │
│ - middlewares: array │
│ - app: App           │
│──────────────────────│
│ + get()              │
│ + post()             │
│ + put()              │
│ + patch()            │
│ + delete()           │
│ + use()              │
│ + dispatch()         │
└──────────┬───────────┘
           │ 创建
           ▼
┌──────────────────────┐
│     Request          │ ◄── 请求对象
│──────────────────────│
│ + params: array      │
│ + query: array       │
│ + body: array        │
│ + headers: array     │
│ + method: string     │
│ + path: string       │
└──────────────────────┘

┌──────────────────────┐
│     Response         │ ◄── 响应对象
│──────────────────────│
│ - statusCode: int    │
│ - headers: array     │
│ - body: mixed        │
│──────────────────────│
│ + status()           │
│ + setHeader()        │
│ + json()             │
│ + text()             │
│ + html()             │
│ + download()         │
│ + redirect()         │
└──────────────────────┘

┌──────────────────────┐
│    Migration         │ ◄── 数据迁移
│──────────────────────│
│ - app: App           │
│──────────────────────│
│ + run()              │
│ - migrateToV1()      │
└──────────────────────┘
```

## 目录职责说明

### `/src/core/` - 核心功能
- **App.php**: 应用核心,提供全局服务
- **Router.php**: 路由系统,处理 HTTP 请求分发

### `/src/utils/` - 工具类
- **Database.php**: 数据库操作封装
- **Migration.php**: 数据版本迁移
- *(待添加)* Download.php: 下载工具
- *(待添加)* Flow.php: 流量处理
- *(待添加)* Geo.php: 地理位置
- *(待添加)* Gist.php: GitHub Gist 操作
- *(待添加)* Cache.php: 缓存管理

### `/src/restful/` - API 路由
*(待实现)* 所有 API 端点的处理器

### `/src/core/proxy-utils/` - 代理工具
*(待实现)* 
- **Parsers/**: 解析各种格式的代理配置
- **Producers/**: 生成目标平台的代理配置
- **Processors/**: 处理代理节点(过滤、排序等)
- **Validators/**: 验证代理配置

### `/public/` - 公开目录
- **index.php**: 唯一入口点,所有请求由此进入

### `/storage/` - 存储目录
- **database.sqlite**: SQLite 数据库文件
- **logs/**: 日志文件
- **cache/**: 缓存文件

### `/cron/` - 定时任务
*(待实现)* 定时同步脚本

## 关键设计模式

### 1. 单例模式 (Singleton)
```php
$app = App::getInstance(); // 始终返回同一实例
```

### 2. 依赖注入 (Dependency Injection)
```php
$router = new Router($app); // 注入 App 依赖
```

### 3. 路由模式 (Routing)
```php
$router->get('/path', $handler); // 注册路由
$router->dispatch(); // 分发请求
```

### 4. 中间件模式 (Middleware)
```php
$router->use(function($req) {
    // 预处理逻辑
});
```

### 5. 数据映射 (Data Mapper)
```php
// Database 类将对象映射到数据库表
$db->insert('subs', $data);
```

## 扩展指南

### 添加新的 API 端点

1. 在 `src/restful/` 创建新文件,例如 `Example.php`:
```php
<?php
namespace SubStore\Restful;

class Example {
    public static function handle($req, $res) {
        $res->json(['message' => 'Hello']);
    }
}
```

2. 在 `src/bootstrap.php` 的 `registerRoutes()` 中注册:
```php
use SubStore\Restful\Example;
$router->get('/api/example', [Example::class, 'handle']);
```

### 添加新的工具类

1. 在 `src/utils/` 创建新文件,例如 `Helper.php`:
```php
<?php
namespace SubStore\Utils;

class Helper {
    public static function doSomething() {
        // 实现逻辑
    }
}
```

2. 使用时直接调用:
```php
use SubStore\Utils\Helper;
Helper::doSomething();
```

### 添加新的数据库表

1. 在 `Database.php` 的 `initializeTables()` 方法中添加:
```php
"CREATE TABLE IF NOT EXISTS new_table (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL
)",
```

2. 在 `Migration.php` 中添加初始化逻辑

## 性能优化建议

1. **启用 OPcache**
   ```ini
   ; php.ini
   opcache.enable=1
   opcache.memory_consumption=128
   opcache.max_accelerated_files=10000
   ```

2. **使用持久化连接**
   ```php
   // Database.php
   $this->pdo->setAttribute(PDO::ATTR_PERSISTENT, true);
   ```

3. **合理缓存**
   - 减少数据库查询
   - 使用内存缓存 (APCu/Redis)

4. **Nginx + PHP-FPM**
   - 比内置服务器性能更好
   - 支持并发请求

## 安全考虑

1. **SQL 注入防护**: 使用预处理语句 ✅
2. **XSS 防护**: 输出时转义 HTML
3. **CSRF 防护**: 实现 Token 验证
4. **输入验证**: 验证所有用户输入
5. **错误处理**: 生产环境隐藏详细错误信息

## 下一步开发优先级

1. ⭐⭐⭐ **代理解析器** - 核心功能
2. ⭐⭐⭐ **代理生成器** - 核心功能  
3. ⭐⭐ **订阅管理 API** - 基本功能
4. ⭐⭐ **下载接口** - 核心功能
5. ⭐ **高级功能** - 增强功能

---

**提示**: 查看 `PROGRESS.md` 了解详细的开发进度和计划。
