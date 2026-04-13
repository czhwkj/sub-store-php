<?php

declare(strict_types=1);

namespace SubStore\Core;

/**
 * 路由系统
 * 实现类似 Express 的路由功能
 */
class Router
{
    private array $routes = [];
    private array $middlewares = [];
    private ?App $app = null;
    
    public function __construct(?App $app = null)
    {
        $this->app = $app ?? App::getInstance();
    }
    
    /**
     * 注册 GET 路由
     * @param string $path 路径
     * @param callable $handler 处理器
     * @return self
     */
    public function get(string $path, callable $handler): self
    {
        $this->addRoute('GET', $path, $handler);
        return $this;
    }
    
    /**
     * 注册 POST 路由
     * @param string $path 路径
     * @param callable $handler 处理器
     * @return self
     */
    public function post(string $path, callable $handler): self
    {
        $this->addRoute('POST', $path, $handler);
        return $this;
    }
    
    /**
     * 注册 PUT 路由
     * @param string $path 路径
     * @param callable $handler 处理器
     * @return self
     */
    public function put(string $path, callable $handler): self
    {
        $this->addRoute('PUT', $path, $handler);
        return $this;
    }
    
    /**
     * 注册 PATCH 路由
     * @param string $path 路径
     * @param callable $handler 处理器
     * @return self
     */
    public function patch(string $path, callable $handler): self
    {
        $this->addRoute('PATCH', $path, $handler);
        return $this;
    }
    
    /**
     * 注册 DELETE 路由
     * @param string $path 路径
     * @param callable $handler 处理器
     * @return self
     */
    public function delete(string $path, callable $handler): self
    {
        $this->addRoute('DELETE', $path, $handler);
        return $this;
    }
    
    /**
     * 注册任意方法的路由
     * @param string $method 请求方法
     * @param string $path 路径
     * @param callable $handler 处理器
     * @return self
     */
    public function route(string $method, string $path, callable $handler): self
    {
        $this->addRoute(strtoupper($method), $path, $handler);
        return $this;
    }
    
    /**
     * 添加路由
     * @param string $method 请求方法
     * @param string $path 路径
     * @param callable $handler 处理器
     */
    private function addRoute(string $method, string $path, callable $handler): void
    {
        // 将路径参数 :name 转换为正则表达式
        $pattern = preg_replace('/:(\w+)/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';
        
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }
    
    /**
     * 注册中间件
     * @param callable $middleware 中间件函数
     * @return self
     */
    public function use(callable $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }
    
    /**
     * 分发请求
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // 移除查询字符串
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        
        // 移除尾部斜杠(除了根路径)
        if ($uri !== '/' && str_ends_with($uri, '/')) {
            $uri = rtrim($uri, '/');
        }
        
        // 查找匹配的路由
        $matchedRoute = null;
        $params = [];
        
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            if (preg_match($route['pattern'], $uri, $matches)) {
                $matchedRoute = $route;
                // 提取命名参数
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = $value;
                    }
                }
                break;
            }
        }
        
        if (!$matchedRoute) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => 'ROUTE_NOT_FOUND',
                'message' => '路由不存在'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        // 构建请求对象
        $request = $this->buildRequest($params);
        
        // 执行中间件
        foreach ($this->middlewares as $middleware) {
            $result = $middleware($request);
            if ($result === false) {
                return; // 中间件阻止了请求
            }
        }
        
        // 执行路由处理器
        try {
            $response = new Response();
            $matchedRoute['handler']($request, $response);
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }
    
    /**
     * 构建请求对象
     * @param array $params 路由参数
     * @return Request
     */
    private function buildRequest(array $params): Request
    {
        return new Request($params);
    }
    
    /**
     * 错误处理
     * @param \Exception $e 异常
     */
    private function handleError(\Exception $e): void
    {
        $this->app->error("路由处理错误: " . $e->getMessage());
        
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        // 不使用 getenv()，直接访问 $_ENV
        $debug = $_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? 'false';
        echo json_encode([
            'success' => false,
            'error' => 'INTERNAL_ERROR',
            'message' => $debug === 'true' ? $e->getMessage() : '服务器内部错误'
        ], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * 请求对象
 */
class Request
{
    public array $params = [];
    public array $query = [];
    public array $body = [];
    public array $headers = [];
    public string $method = '';
    public string $path = '';
    
    public function __construct(array $params = [])
    {
        $this->params = $params;
        $this->query = $_GET;
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // 解析请求体
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $input = file_get_contents('php://input');
            $this->body = json_decode($input, true) ?? [];
        } else {
            $this->body = $_POST;
        }
        
        // 获取请求头
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerName = str_replace('_', '-', substr($key, 5));
                $this->headers[$headerName] = $value;
            }
        }
    }
}

/**
 * 响应对象
 */
class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private mixed $body = null;
    
    /**
     * 设置状态码
     * @param int $code 状态码
     * @return self
     */
    public function status(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }
    
    /**
     * 设置响应头
     * @param string $key 键名
     * @param string $value 值
     * @return self
     */
    public function setHeader(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }
    
    /**
     * 发送 JSON 响应
     * @param mixed $data 数据
     * @param int $statusCode HTTP状态码
     */
    public function json(mixed $data, int $statusCode = 200): void
    {
        $this->statusCode = $statusCode;
        $this->setHeader('Content-Type', 'application/json; charset=utf-8');
        $this->body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $this->send();
    }
    
    /**
     * 发送文本响应
     * @param string $text 文本
     */
    public function text(string $text): void
    {
        $this->setHeader('Content-Type', 'text/plain; charset=utf-8');
        $this->body = $text;
        $this->send();
    }
    
    /**
     * 发送 HTML 响应
     * @param string $html HTML
     */
    public function html(string $html): void
    {
        $this->setHeader('Content-Type', 'text/html; charset=utf-8');
        $this->body = $html;
        $this->send();
    }
    
    /**
     * 发送文件下载
     * @param string $content 文件内容
     * @param string $filename 文件名
     * @param string $contentType 内容类型
     */
    public function download(string $content, string $filename, string $contentType = 'application/octet-stream'): void
    {
        $this->setHeader('Content-Type', $contentType);
        $this->setHeader('Content-Disposition', "attachment; filename=\"{$filename}\"");
        $this->setHeader('Content-Length', (string) strlen($content));
        $this->body = $content;
        $this->send();
    }
    
    /**
     * 重定向
     * @param string $url URL
     * @param int $code 状态码
     */
    public function redirect(string $url, int $code = 302): void
    {
        http_response_code($code);
        header("Location: {$url}");
        exit;
    }
    
    /**
     * 发送响应
     */
    private function send(): void
    {
        http_response_code($this->statusCode);
        
        foreach ($this->headers as $key => $value) {
            header("{$key}: {$value}");
        }
        
        echo $this->body;
        exit;
    }
}
