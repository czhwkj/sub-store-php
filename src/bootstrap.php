<?php

declare(strict_types=1);

/**
 * 启动文件
 * 加载所有必要的类并初始化应用
 */

// 设置错误报告
error_reporting(E_ALL);
// 不使用 getenv()，直接访问 $_ENV
$debug = $_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? 'false';
ini_set('display_errors', $debug === 'true' ? '1' : '0');

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 自动加载器
spl_autoload_register(function (string $class) {
    $prefix = 'SubStore\\';
    $baseDir = __DIR__ . '/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// 加载常量
require_once __DIR__ . '/constants.php';

use SubStore\Core\App;
use SubStore\Core\Router;
use SubStore\Utils\Migration;
use SubStore\Restful\SubscriptionsRoute;
use SubStore\Restful\CollectionsRoute;
use SubStore\Restful\ArtifactsRoute;
use SubStore\Restful\FileRoute;
use SubStore\Restful\SettingsRoute;
use SubStore\Restful\SyncRoute;
use SubStore\Restful\TokenRoute;
use SubStore\Restful\ArchiveRoute;
use SubStore\Restful\ModuleRoute;
use SubStore\Restful\DownloadRoute;

// 获取应用实例
$app = App::getInstance();

// 运行数据迁移
try {
    $migration = new Migration($app);
    $migration->run();
} catch (\Exception $e) {
    $app->error("数据迁移失败: " . $e->getMessage());
}

// 创建路由器
$router = new Router($app);

// 注册路由模块
new SubscriptionsRoute($app, $router);
new CollectionsRoute($app, $router);
new ArtifactsRoute($app, $router);
new FileRoute($app, $router);
new SettingsRoute($app, $router);
new SyncRoute($app, $router);
new TokenRoute($app, $router);
new ArchiveRoute($app, $router);
new ModuleRoute($app, $router);
new DownloadRoute($app, $router);

// 健康检查和测试路由
$router->get('/api/test', function ($req, $res) {
    $res->json([
        'success' => true,
        'message' => 'Sub-Store PHP 版本运行正常',
        'version' => '1.0.0'
    ]);
});

$router->get('/health', function ($req, $res) {
    $res->json([
        'status' => 'ok',
        'timestamp' => time()
    ]);
});

return $router;
