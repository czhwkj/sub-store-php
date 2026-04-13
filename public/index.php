<?php

declare(strict_types=1);

/**
 * 入口文件
 * Sub-Store PHP 版本的入口点
 */

// 加载 .env 文件
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        // 不使用 putenv()，因为某些服务器可能禁用了它
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

// 如果是访问根路径且不是 API 请求，返回前端页面
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($uri === '/' || $uri === '/index.html') {
    $htmlFile = __DIR__ . '/index.html';
    if (file_exists($htmlFile)) {
        header('Content-Type: text/html; charset=utf-8');
        readfile($htmlFile);
        exit;
    }
}

// 加载启动文件
try {
    $router = require_once __DIR__ . '/../src/bootstrap.php';
    // 分发请求
    $router->dispatch();
} catch (\Throwable $e) {
    // 捕获所有错误并返回 JSON
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => 'BOOTSTRAP_ERROR',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => getenv('APP_DEBUG') === 'true' ? $e->getTraceAsString() : null
    ], JSON_UNESCAPED_UNICODE);
}
