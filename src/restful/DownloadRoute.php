<?php

declare(strict_types=1);

namespace SubStore\Restful;

use SubStore\Core\Router;
use SubStore\Core\App;
use SubStore\Restful\errors as Errors;
use SubStore\Core\ProxyUtils\ProxyParser;
use SubStore\Core\ProxyUtils\ProxyProducer;
use SubStore\Core\ProxyUtils\ProxyUtils;

class DownloadRoute
{
    private App $app;
    private Router $router;

    public function __construct(App $app, Router $router)
    {
        $this->app = $app;
        $this->router = $router;
        $this->register();
    }

    private function register(): void
    {
        // 先注册更具体的路由（避免被通用路由拦截）
        
        // GET /download/collection/:name - 下载组合订阅
        $this->router->get('/download/collection/:name', [$this, 'downloadCollection']);

        // GET /download/collection/:name/:target - 下载组合订阅(指定平台)
        $this->router->get('/download/collection/:name/:target', [$this, 'downloadCollection']);

        // 分享链接 - 组合订阅
        $this->router->get('/share/col/:name', [$this, 'downloadCollection']);
        $this->router->get('/share/col/:name/:target', [$this, 'downloadCollection']);

        // 再注册通用路由
        // GET /download/:name - 下载订阅
        $this->router->get('/download/:name', [$this, 'downloadSubscription']);

        // GET /download/:name/:target - 下载订阅(指定平台)
        $this->router->get('/download/:name/:target', [$this, 'downloadSubscription']);

        // 分享链接 - 单个订阅
        $this->router->get('/share/sub/:name', [$this, 'downloadSubscription']);
        $this->router->get('/share/sub/:name/:target', [$this, 'downloadSubscription']);
    }

    public function downloadSubscription($req, $res): void
    {
        $name = $req->params['name'];
        $target = $req->params['target'] ?? null;
        
        if ($target) {
            $req->query['target'] = $target;
        }

        $platform = $req->query['target'] ?? 'JSON';
        $reqUA = $req->headers['User-Agent'] ?? $req->headers['user-agent'] ?? '';
        
        $this->app->info("正在下载订阅: {$name}, 目标平台: {$platform}, UA: {$reqUA}");

        $allSubs = $this->app->read(SUBS_KEY) ?? [];
        $sub = $this->findByName($allSubs, $name);

        if ($sub) {
            try {
                $proxies = [];
                $content = '';
                
                // 如果有缓存的代理数据，直接使用
                if (isset($sub['proxies']) && is_array($sub['proxies'])) {
                    $proxies = $sub['proxies'];
                    $this->app->info("使用缓存的代理数据: " . count($proxies) . " 个节点");
                } 
                // 如果有缓存的内容，解析它
                else if (isset($sub['content']) && !empty($sub['content'])) {
                    $content = $sub['content'];
                    $parser = new ProxyParser();
                    $proxies = $parser->parse($content);
                    $this->app->info("解析缓存内容: " . strlen($content) . " 字节, " . count($proxies) . " 个节点");
                }
                // 如果是远程订阅且没有缓存，从远程 URL 获取
                else if ($sub['source'] === 'remote' && !empty($sub['url'])) {
                    $this->app->info("从远程 URL 获取订阅: {$sub['url']}");
                    $content = \SubStore\Utils\Download::fetchSubscription($sub);
                    $parser = new ProxyParser();
                    $proxies = $parser->parse($content);
                    $this->app->info("抓取并解析成功: " . strlen($content) . " 字节, " . count($proxies) . " 个节点");
                }
                
                // 应用代理处理
                if (!empty($proxies)) {
                    // 去重
                    $proxies = ProxyUtils::deduplicate($proxies);
                    
                    // 排序
                    $proxies = ProxyUtils::sort($proxies, 'name', 'asc');
                }
                
                // 生成目标格式
                $content = ProxyProducer::produce($proxies, $platform);
                
                // 设置 Content-Type
                $mimeTypes = [
                    'json' => 'application/json; charset=utf-8',
                    'clash' => 'text/plain; charset=utf-8',
                    'surge' => 'text/plain; charset=utf-8',
                    'loon' => 'text/plain; charset=utf-8',
                    'qx' => 'text/plain; charset=utf-8',
                    'v2ray' => 'text/plain; charset=utf-8',
                    'singbox' => 'application/json; charset=utf-8',
                ];
                
                $contentType = $mimeTypes[strtolower($platform)] ?? 'text/plain; charset=utf-8';
                $res->setHeader('Content-Type', $contentType);
                $res->text($content);
                
                $this->app->info("下载订阅成功: {$name}, 共 " . count($proxies) . " 个节点");
            } catch (\Exception $e) {
                $this->app->error("下载订阅失败: {$name}, 原因: {$e->getMessage()}");
                $this->failed($res, new Errors\InternalServerError(
                    'INTERNAL_SERVER_ERROR',
                    "Failed to download subscription: {$name}",
                    $e->getMessage()
                ));
            }
        } else {
            $this->failed($res, new Errors\ResourceNotFoundError(
                'RESOURCE_NOT_FOUND',
                "Subscription {$name} does not exist!"
            ), 404);
        }
    }

    public function downloadCollection($req, $res): void
    {
        $name = $req->params['name'];
        $target = $req->params['target'] ?? null;
        
        if ($target) {
            $req->query['target'] = $target;
        }

        $platform = $req->query['target'] ?? 'JSON';
        
        $this->app->info("正在下载组合订阅: {$name}, 目标平台: {$platform}");

        $allCols = $this->app->read(COLLECTIONS_KEY) ?? [];
        $collection = $this->findByName($allCols, $name);

        if ($collection) {
            try {
                // 合并多个订阅的节点
                $subNames = $collection['subscriptions'] ?? [];
                $allSubs = $this->app->read(SUBS_KEY) ?? [];
                
                $allProxies = [];
                $parser = new ProxyParser();
                
                foreach ($subNames as $subName) {
                    $sub = $this->findByName($allSubs, $subName);
                    if ($sub) {
                        // 如果有缓存的代理数据
                        if (isset($sub['proxies']) && is_array($sub['proxies'])) {
                            $allProxies = array_merge($allProxies, $sub['proxies']);
                        }
                        // 否则解析原始内容
                        else if (isset($sub['content']) && !empty($sub['content'])) {
                            $proxies = $parser->parse($sub['content']);
                            $allProxies = array_merge($allProxies, $proxies);
                        }
                    }
                }
                
                // 应用代理处理
                if (!empty($allProxies)) {
                    // 去重
                    $allProxies = ProxyUtils::deduplicate($allProxies);
                    
                    // 排序
                    $allProxies = ProxyUtils::sort($allProxies, 'name', 'asc');
                }
                
                // 生成目标格式
                $content = ProxyProducer::produce($allProxies, $platform);
                
                // 设置 Content-Type
                $mimeTypes = [
                    'json' => 'application/json; charset=utf-8',
                    'clash' => 'text/plain; charset=utf-8',
                    'surge' => 'text/plain; charset=utf-8',
                    'loon' => 'text/plain; charset=utf-8',
                    'qx' => 'text/plain; charset=utf-8',
                    'v2ray' => 'text/plain; charset=utf-8',
                    'singbox' => 'application/json; charset=utf-8',
                ];
                
                $contentType = $mimeTypes[strtolower($platform)] ?? 'text/plain; charset=utf-8';
                $res->setHeader('Content-Type', $contentType);
                $res->text($content);
                
                $this->app->info("下载组合订阅成功: {$name}, 共 " . count($allProxies) . " 个节点");
            } catch (\Exception $e) {
                $this->app->error("下载组合订阅失败: {$name}, 原因: {$e->getMessage()}");
                $this->failed($res, new Errors\InternalServerError(
                    'INTERNAL_SERVER_ERROR',
                    "Failed to download collection: {$name}",
                    $e->getMessage()
                ));
            }
        } else {
            $this->failed($res, new Errors\ResourceNotFoundError(
                'RESOURCE_NOT_FOUND',
                "Collection {$name} does not exist!"
            ), 404);
        }
    }

    private function findByName(array $items, string $name): ?array
    {
        foreach ($items as $item) {
            if ($item['name'] === $name) {
                return $item;
            }
        }
        return null;
    }

    private function failed($res, \Exception $error, ?int $httpCode = null): void
    {
        $statusCode = $httpCode ?? $error->getCode();
        $res->json([
            'success' => false,
            'error' => $error instanceof Errors\BaseError ? $error->getErrorType() : 'INTERNAL_ERROR',
            'message' => $error->getMessage()
        ], $statusCode);
    }
}
