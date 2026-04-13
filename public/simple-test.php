<?php
// 简单错误测试 - 直接输出错误信息
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

header('Content-Type: text/html; charset=utf-8');

echo "<h1>PHP 错误测试</h1>";
echo "<h2>PHP 版本: " . phpversion() . "</h2>";

try {
    echo "<h3>1. 测试加载 constants.php</h3>";
    require_once __DIR__ . '/../src/constants.php';
    echo "<p style='color:green'>✅ constants.php 加载成功</p>";
    echo "<p>SUBS_KEY = " . SUBS_KEY . "</p>";
} catch (Throwable $e) {
    echo "<p style='color:red'>❌ 错误: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

try {
    echo "<h3>2. 测试加载 App.php</h3>";
    require_once __DIR__ . '/../src/utils/Database.php';
    require_once __DIR__ . '/../src/core/App.php';
    echo "<p style='color:green'>✅ App.php 加载成功</p>";
} catch (Throwable $e) {
    echo "<p style='color:red'>❌ 错误: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

try {
    echo "<h3>3. 测试 App 实例化</h3>";
    $app = \SubStore\Core\App::getInstance();
    echo "<p style='color:green'>✅ App 实例化成功</p>";
} catch (Throwable $e) {
    echo "<p style='color:red'>❌ 错误: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

try {
    echo "<h3>4. 测试 Router 加载</h3>";
    require_once __DIR__ . '/../src/core/Router.php';
    echo "<p style='color:green'>✅ Router.php 加载成功</p>";
} catch (Throwable $e) {
    echo "<p style='color:red'>❌ 错误: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

try {
    echo "<h3>5. 测试路由模块加载</h3>";
    require_once __DIR__ . '/../src/restful/errors.php';
    require_once __DIR__ . '/../src/restful/subscriptions.php';
    echo "<p style='color:green'>✅ SubscriptionsRoute 加载成功</p>";
} catch (Throwable $e) {
    echo "<p style='color:red'>❌ 错误: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

try {
    echo "<h3>6. 测试 bootstrap.php</h3>";
    // 设置环境变量
    $envFile = __DIR__ . '/../.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '#') === 0) continue;
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
            $_ENV[trim($key)] = trim($value);
        }
        echo "<p style='color:green'>✅ .env 文件加载成功</p>";
    }
    
    $router = require_once __DIR__ . '/../src/bootstrap.php';
    echo "<p style='color:green'>✅ bootstrap.php 加载成功</p>";
} catch (Throwable $e) {
    echo "<p style='color:red'>❌ 错误: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h2>所有测试完成</h2>";
echo "<p>如果以上有任何红色错误，请截图发给我。</p>";
echo "<p>如果全部通过，说明代码加载没问题，500 错误可能由其他原因引起。</p>";
