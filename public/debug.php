<?php
// PHP 错误诊断脚本
error_reporting(E_ALL);
ini_set('display_errors', '1');

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>PHP 错误诊断</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; }
        h2 { color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        .success { color: green; }
        .error { color: red; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .info { background: #e7f3ff; padding: 10px; border-left: 4px solid #2196F3; margin: 10px 0; }
        .warning { background: #fff3cd; padding: 10px; border-left: 4px solid #ffc107; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🔧 PHP 环境诊断</h1>
    
    <div class="section">
        <h2>1. PHP 基本信息</h2>
        <?php
        echo "<p class='success'>✅ PHP 版本: " . phpversion() . "</p>";
        echo "<p>PHP SAPI: " . php_sapi_name() . "</p>";
        echo "<p>服务器软件: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "</p>";
        echo "<p>文档根目录: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "</p>";
        echo "<p>当前脚本: " . __FILE__ . "</p>";
        ?>
    </div>

    <div class="section">
        <h2>2. 必需扩展检查</h2>
        <?php
        $extensions = ['pdo', 'pdo_sqlite', 'curl', 'mbstring', 'json'];
        foreach ($extensions as $ext) {
            if (extension_loaded($ext)) {
                echo "<p class='success'>✅ {$ext} - 已安装</p>";
            } else {
                echo "<p class='error'>❌ {$ext} - 未安装</p>";
            }
        }
        ?>
    </div>

    <div class="section">
        <h2>3. 文件和目录权限</h2>
        <?php
        $paths = [
            '应用根目录' => __DIR__ . '/../',
            'src 目录' => __DIR__ . '/../src/',
            'storage 目录' => __DIR__ . '/../storage/',
            '数据库文件' => __DIR__ . '/../storage/database.sqlite',
            'bootstrap.php' => __DIR__ . '/../src/bootstrap.php',
            'constants.php' => __DIR__ . '/../src/constants.php',
        ];

        foreach ($paths as $name => $path) {
            if (file_exists($path)) {
                $perms = substr(sprintf('%o', fileperms($path)), -4);
                $writable = is_writable($path) ? '可写' : '不可写';
                $readable = is_readable($path) ? '可读' : '不可读';
                echo "<p class='success'>✅ {$name}</p>";
                echo "<pre>路径: {$path}\n权限: {$perms}\n可读: {$readable}\n可写: {$writable}</pre>";
            } else {
                echo "<p class='error'>❌ {$name} - 文件/目录不存在</p>";
                echo "<pre>期望路径: {$path}</pre>";
            }
        }
        ?>
    </div>

    <div class="section">
        <h2>4. 加载核心文件测试</h2>
        <?php
        try {
            echo "<p>尝试加载 constants.php...</p>";
            require_once __DIR__ . '/../src/constants.php';
            echo "<p class='success'>✅ constants.php 加载成功</p>";
            echo "<p>SUBS_KEY 常量: " . SUBS_KEY . "</p>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ constants.php 加载失败: " . $e->getMessage() . "</p>";
        }

        try {
            echo "<hr><p>尝试加载 App 类...</p>";
            require_once __DIR__ . '/../src/core/App.php';
            echo "<p class='success'>✅ App.php 加载成功</p>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ App.php 加载失败: " . $e->getMessage() . "</p>";
        }

        try {
            echo "<hr><p>尝试加载 Router 类...</p>";
            require_once __DIR__ . '/../src/core/Router.php';
            echo "<p class='success'>✅ Router.php 加载成功</p>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ Router.php 加载失败: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>

    <div class="section">
        <h2>5. 数据库连接测试</h2>
        <?php
        try {
            $dbPath = __DIR__ . '/../storage/database.sqlite';
            $dbDir = dirname($dbPath);
            
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0755, true);
                echo "<p class='warning'>⚠️ 创建了 storage 目录</p>";
            }
            
            $pdo = new PDO("sqlite:{$dbPath}");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "<p class='success'>✅ 数据库连接成功</p>";
            
            // 测试写入
            $stmt = $pdo->exec("CREATE TABLE IF NOT EXISTS test_table (id INTEGER PRIMARY KEY, test TEXT)");
            echo "<p class='success'>✅ 表创建成功</p>";
            
            $stmt = $pdo->exec("INSERT INTO test_table (test) VALUES ('test')");
            echo "<p class='success'>✅ 数据插入成功</p>";
            
            $result = $pdo->query("SELECT * FROM test_table")->fetchAll();
            echo "<p class='success'>✅ 数据查询成功</p>";
            
            $pdo->exec("DROP TABLE test_table");
            echo "<p class='success'>✅ 测试表已清理</p>";
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ 数据库操作失败: " . $e->getMessage() . "</p>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }
        ?>
    </div>

    <div class="section">
        <h2>6. App 类实例化测试</h2>
        <?php
        try {
            require_once __DIR__ . '/../src/utils/Database.php';
            require_once __DIR__ . '/../src/core/App.php';
            
            $app = \SubStore\Core\App::getInstance();
            echo "<p class='success'>✅ App 实例化成功</p>";
            
            // 测试读写
            $app->write('test_key', ['test' => 'data']);
            echo "<p class='success'>✅ App::write() 成功</p>";
            
            $data = $app->read('test_key');
            echo "<p class='success'>✅ App::read() 成功: " . json_encode($data) . "</p>";
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ App 测试失败: " . $e->getMessage() . "</p>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }
        ?>
    </div>

    <div class="section">
        <h2>7. 路由模块加载测试</h2>
        <?php
        try {
            $app = \SubStore\Core\App::getInstance();
            
            echo "<p>尝试加载 SubscriptionsRoute...</p>";
            require_once __DIR__ . '/../src/restful/errors.php';
            require_once __DIR__ . '/../src/restful/SubscriptionsRoute.php';
            echo "<p class='success'>✅ SubscriptionsRoute 类加载成功</p>";
            
            // 尝试创建 Router
            $router = new \SubStore\Core\Router($app);
            echo "<p class='success'>✅ Router 实例化成功</p>";
            
            // 尝试注册路由
            $subRoute = new \SubStore\Restful\SubscriptionsRoute($app, $router);
            echo "<p class='success'>✅ SubscriptionsRoute 注册成功</p>";
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ 路由模块测试失败: " . $e->getMessage() . "</p>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }
        ?>
    </div>

    <div class="section">
        <h2>8. 环境变量</h2>
        <pre><?php
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            echo "✅ .env 文件存在\n";
            echo "内容:\n" . file_get_contents($envFile);
        } else {
            echo "❌ .env 文件不存在\n";
            if (file_exists($envFile . '.example')) {
                echo "✅ .env.example 存在，建议复制为 .env";
            }
        }
        ?></pre>
    </div>

    <div class="section">
        <h2>9. 模拟 API 请求测试</h2>
        <?php
        try {
            // 模拟 POST 请求创建订阅
            require_once __DIR__ . '/../src/bootstrap.php';
            
            echo "<p class='success'>✅ bootstrap.php 加载成功</p>";
            echo "<p class='info'>💡 如果以上所有测试都通过，说明环境配置正确。<br>
            HTTP 500 错误可能由以下原因引起：<br>
            1. Nginx 伪静态配置问题<br>
            2. 请求数据格式问题<br>
            3. 数据库写入权限问题<br>
            4. PHP 内存限制</p>";
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ 模拟请求失败: " . $e->getMessage() . "</p>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }
        ?>
    </div>

    <div class="section">
        <h2>10. 常见错误和解决方案</h2>
        <div class="warning">
            <strong>如果遇到 "Class not found" 错误：</strong><br>
            - 检查自动加载器是否正确<br>
            - 确认所有文件都已上传<br>
            - 检查命名空间是否匹配<br><br>
            
            <strong>如果遇到 "Permission denied" 错误：</strong><br>
            - 检查 storage 目录权限（需要 755）<br>
            - 检查 database.sqlite 文件权限（需要 666）<br>
            - Windows 下检查 IIS/IIS_USER 权限<br><br>
            
            <strong>如果遇到 "Class 'PDO' not found" 错误：</strong><br>
            - 确认已安装 PDO 和 PDO SQLite 扩展<br>
            - 检查 php.ini 中是否启用了 extension=pdo_sqlite<br><br>
            
            <strong>如果所有测试通过但仍 500：</strong><br>
            - 查看 Nginx 错误日志<br>
            - 查看 PHP 错误日志<br>
            - 检查 PHP 内存限制（建议 256M）
        </div>
    </div>
</body>
</html>
