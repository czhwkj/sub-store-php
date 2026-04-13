<?php

declare(strict_types=1);

namespace SubStore\Core;

use SubStore\Utils\Database;
use PDO;
use Exception;

/**
 * 应用核心类
 * 替代原 @/core/app.js 的 OpenAPI 类
 */
class App
{
    private static ?App $instance = null;
    private Database $db;
    private array $config = [];
    private string $logFile;
    
    private function __construct()
    {
        // 加载环境变量
        $this->loadEnv();
        
        // 初始化数据库 - 使用 $_ENV 或 $_SERVER 而不是 getenv()
        $dbPath = $_ENV['DB_PATH'] ?? $_SERVER['DB_PATH'] ?? __DIR__ . '/../storage/database.sqlite';
        $this->db = new Database($dbPath);
        
        // 设置日志文件
        $this->logFile = __DIR__ . '/../storage/logs/app.log';
        
        // 确保日志目录存在
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    /**
     * 获取单例实例
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 加载环境变量
     */
    private function loadEnv(): void
    {
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    // 不使用 putenv()，直接使用 $_ENV 和 $_SERVER
                    if (!isset($_ENV[$key])) {
                        $_ENV[$key] = $value;
                    }
                    if (!isset($_SERVER[$key])) {
                        $_SERVER[$key] = $value;
                    }
                }
            }
        }
    }
    
    /**
     * 读取数据
     * @param string $key 键名
     * @return mixed
     */
    public function read(string $key): mixed
    {
        try {
            $result = $this->db->selectOne('settings', 'value', ['key' => $key]);
            if ($result === false) {
                return null;
            }
            return json_decode($result['value'], true);
        } catch (Exception $e) {
            $this->error("读取数据失败: {$key} - " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 写入数据
     * @param string $key 键名
     * @param mixed $value 值
     * @return bool
     */
    public function write(string $key, mixed $value): bool
    {
        try {
            $jsonValue = json_encode($value, JSON_UNESCAPED_UNICODE);
            
            // 检查是否已存在
            $existing = $this->db->selectOne('settings', 'id', ['key' => $key]);
            
            if ($existing) {
                return $this->db->update('settings', ['value' => $jsonValue], ['key' => $key]) > 0;
            } else {
                return $this->db->insert('settings', [
                    'key' => $key,
                    'value' => $jsonValue
                ]) > 0;
            }
        } catch (Exception $e) {
            $this->error("写入数据失败: {$key} - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 删除数据
     * @param string $key 键名
     * @return bool
     */
    public function delete(string $key): bool
    {
        try {
            return $this->db->delete('settings', ['key' => $key]) > 0;
        } catch (Exception $e) {
            $this->error("删除数据失败: {$key} - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 记录信息日志
     * @param string $message 消息
     */
    public function info(string $message): void
    {
        $this->log('INFO', $message);
        // 不使用 getenv()，直接访问 $_ENV
        if (($_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? 'false') === 'true') {
            error_log("[INFO] {$message}");
        }
    }
    
    /**
     * 记录错误日志
     * @param string $message 消息
     */
    public function error(string $message): void
    {
        $this->log('ERROR', $message);
        error_log("[ERROR] {$message}");
    }
    
    /**
     * 记录警告日志
     * @param string $message 消息
     */
    public function warning(string $message): void
    {
        $this->log('WARNING', $message);
    }
    
    /**
     * 写入日志文件
     * @param string $level 日志级别
     * @param string $message 消息
     */
    private function log(string $level, string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * HTTP GET 请求
     * @param string $url URL
     * @param array $options 选项
     * @return array ['body' => string, 'headers' => array, 'status' => int]
     */
    public function httpGet(string $url, array $options = []): array
    {
        return $this->httpRequest('GET', $url, $options);
    }
    
    /**
     * HTTP POST 请求
     * @param string $url URL
     * @param array $options 选项
     * @return array ['body' => string, 'headers' => array, 'status' => int]
     */
    public function httpPost(string $url, array $options = []): array
    {
        return $this->httpRequest('POST', $url, $options);
    }
    
    /**
     * HTTP PUT 请求
     * @param string $url URL
     * @param array $options 选项
     * @return array
     */
    public function httpPut(string $url, array $options = []): array
    {
        return $this->httpRequest('PUT', $url, $options);
    }
    
    /**
     * HTTP PATCH 请求
     * @param string $url URL
     * @param array $options 选项
     * @return array
     */
    public function httpPatch(string $url, array $options = []): array
    {
        return $this->httpRequest('PATCH', $url, $options);
    }
    
    /**
     * HTTP DELETE 请求
     * @param string $url URL
     * @param array $options 选项
     * @return array
     */
    public function httpDelete(string $url, array $options = []): array
    {
        return $this->httpRequest('DELETE', $url, $options);
    }
    
    /**
     * 通用 HTTP 请求方法
     * @param string $method 请求方法
     * @param string $url URL
     * @param array $options 选项
     * @return array
     */
    private function httpRequest(string $method, string $url, array $options = []): array
    {
        $ch = curl_init($url);
        
        // 默认选项
        $defaultOptions = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => $options['headers'] ?? [],
        ];
        
        // 根据方法设置
        switch (strtoupper($method)) {
            case 'POST':
                $defaultOptions[CURLOPT_POST] = true;
                if (isset($options['body'])) {
                    $defaultOptions[CURLOPT_POSTFIELDS] = $options['body'];
                }
                break;
            case 'PUT':
            case 'PATCH':
            case 'DELETE':
                $defaultOptions[CURLOPT_CUSTOMREQUEST] = $method;
                if (isset($options['body'])) {
                    $defaultOptions[CURLOPT_POSTFIELDS] = $options['body'];
                }
                break;
        }
        
        // 合并选项
        $allOptions = array_merge($defaultOptions, $options['curl'] ?? []);
        
        curl_setopt_array($ch, $allOptions);
        
        $body = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $rawHeaders = substr($body, 0, $headerSize);
        $body = substr($body, $headerSize);
        
        // 解析头
        $headers = [];
        $headerLines = explode("\r\n", trim($rawHeaders));
        foreach ($headerLines as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $headers[trim($key)] = trim($value);
            }
        }
        
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("HTTP 请求失败: {$error}");
        }
        
        return [
            'body' => $body,
            'headers' => $headers,
            'status' => $statusCode,
        ];
    }
    
    /**
     * 获取数据库实例
     * @return Database
     */
    public function getDatabase(): Database
    {
        return $this->db;
    }
    
    /**
     * 获取配置项
     * @param string $key 键名
     * @param mixed $default 默认值
     * @return mixed
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }
    
    /**
     * 设置配置项
     * @param string $key 键名
     * @param mixed $value 值
     */
    public function setConfig(string $key, mixed $value): void
    {
        $this->config[$key] = $value;
    }
    
    /**
     * 检查是否为 Node.js 环境(兼容原代码)
     * @return bool
     */
    public function isNode(): bool
    {
        return false; // PHP 环境始终返回 false
    }
}
