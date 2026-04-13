<?php

declare(strict_types=1);

namespace SubStore\Restful;

use SubStore\Core\Router;
use SubStore\Core\App;
use SubStore\Restful\BaseError;
use SubStore\Restful\RequestInvalidError;
use SubStore\Restful\InternalServerError;
use SubStore\Restful\ResourceNotFoundError;
use SubStore\Utils\Archive;

class SubscriptionsRoute
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
        // 确保订阅数据存在
        if (!$this->app->read(SUBS_KEY)) {
            $this->app->write(SUBS_KEY, []);
        }

        // GET /api/sub/flow/:name - 获取订阅流量信息
        $this->router->get('/api/sub/flow/:name', [$this, 'getFlowInfo']);

        // GET /api/sub/:name - 获取订阅详情
        $this->router->get('/api/sub/:name', [$this, 'getSubscription']);

        // PATCH /api/sub/:name - 更新订阅
        $this->router->patch('/api/sub/:name', [$this, 'updateSubscription']);

        // DELETE /api/sub/:name - 删除订阅
        $this->router->delete('/api/sub/:name', [$this, 'deleteSubscription']);

        // GET /api/subs - 获取所有订阅
        $this->router->get('/api/subs', [$this, 'getAllSubscriptions']);

        // POST /api/subs - 创建订阅
        $this->router->post('/api/subs', [$this, 'createSubscription']);

        // PUT /api/subs - 替换所有订阅
        $this->router->put('/api/subs', [$this, 'replaceSubscriptions']);
    }

    /**
     * 获取订阅流量信息
     */
    public function getFlowInfo($req, $res): void
    {
        $name = $req->params['name'];
        $url = $req->query['url'] ?? null;

        $allSubs = $this->app->read(SUBS_KEY) ?? [];
        $sub = $this->findByName($allSubs, $name);

        if (!$sub) {
            $this->failed($res, new ResourceNotFoundError(
                'RESOURCE_NOT_FOUND',
                "Subscription {$name} does not exist!"
            ), 404);
            return;
        }

        // 本地订阅处理
        if ($sub['source'] === 'local' && !in_array($sub['mergeSources'] ?? '', ['localFirst', 'remoteFirst'])) {
            if (!empty($sub['subUserinfo'])) {
                $subUserInfo = $sub['subUserinfo'];
                if (preg_match('/^https?:\/\//', $subUserInfo)) {
                    // TODO: 获取远程流量信息
                }
                $res->json([
                    'success' => true,
                    'data' => $this->parseFlowHeaders($subUserInfo)
                ]);
            } else {
                $this->failed($res, new RequestInvalidError(
                    'NO_FLOW_INFO',
                    'N/A',
                    "Local subscription {$name} has no flow information!"
                ));
            }
            return;
        }

        // 远程订阅处理
        // TODO: 实现远程订阅流量获取
        $this->failed($res, new InternalServerError(
            'NO_FLOW_INFO',
            'Not implemented yet'
        ));
    }

    /**
     * 创建订阅
     */
    public function createSubscription($req, $res): void
    {
        try {
            $sub = $req->body;
            $this->validateSubscriptionName($sub['name'] ?? '');
            
            $allSubs = $this->app->read(SUBS_KEY) ?? [];
            
            if ($this->findByName($allSubs, $sub['name'])) {
                throw new RequestInvalidError(
                    'DUPLICATE_KEY',
                    "Subscription {$sub['name']} already exists."
                );
            }

            // 如果是远程订阅，立即抓取内容并解析节点
            if ($sub['source'] === 'remote' && !empty($sub['url'])) {
                try {
                    $this->app->info("正在抓取远程订阅: {$sub['name']}, URL: {$sub['url']}");
                    $content = \SubStore\Utils\Download::fetchSubscription($sub);
                    $sub['content'] = $content;
                    $sub['updatedAt'] = time();
                    
                    // 解析代理节点并缓存
                    try {
                        $parser = new \SubStore\Core\ProxyUtils\ProxyParser();
                        $proxies = $parser->parse($content);
                        $sub['proxies'] = $proxies;
                        $this->app->info("抓取成功: " . strlen($content) . " 字节, 解析出 " . count($proxies) . " 个节点");
                    } catch (\Exception $parseError) {
                        $this->app->warning("解析节点失败: {$parseError->getMessage()}");
                        $sub['parseError'] = $parseError->getMessage();
                    }
                } catch (\Exception $e) {
                    $this->app->error("抓取远程订阅失败: {$e->getMessage()}");
                    // 不中断创建，但记录错误
                    $sub['error'] = $e->getMessage();
                }
            }

            $sub['createdAt'] = time();
            $sub['updatedAt'] = time();
            
            $allSubs[] = $sub;
            $this->app->write(SUBS_KEY, $allSubs);
            
            $res->json([
                'success' => true,
                'data' => $sub
            ], 201);
            
            $this->app->info("创建订阅: {$sub['name']}");
        } catch (\Exception $e) {
            $this->failed($res, $e);
        }
    }

    /**
     * 获取订阅
     */
    public function getSubscription($req, $res): void
    {
        $name = $req->params['name'];
        $raw = $req->query['raw'] ?? null;

        $allSubs = $this->app->read(SUBS_KEY) ?? [];
        $sub = $this->findByName($allSubs, $name);

        if ($sub) {
            unset($sub['subscriptions']);
            if ($raw) {
                $res->setHeader('Content-Type', 'application/json')
                    ->setHeader('Content-Disposition', "attachment; filename=\"sub_store_subscription_{$name}.json\"")
                    ->text(json_encode($sub, JSON_UNESCAPED_UNICODE));
            } else {
                $res->json([
                    'success' => true,
                    'data' => $sub
                ]);
            }
        } else {
            $this->failed($res, new ResourceNotFoundError(
                'SUBSCRIPTION_NOT_FOUND',
                "Subscription {$name} does not exist"
            ), 404);
        }
    }

    /**
     * 更新订阅
     */
    public function updateSubscription($req, $res): void
    {
        $name = $req->params['name'];
        $updateData = $req->body;
        unset($updateData['subscriptions']);

        $allSubs = $this->app->read(SUBS_KEY) ?? [];
        $oldSub = $this->findByName($allSubs, $name);

        if ($oldSub) {
            if (empty($updateData['name'])) {
                $updateData['name'] = $oldSub['name'];
            }
            
            $newSub = array_merge($oldSub, $updateData);
            $newSub['updatedAt'] = time();
            
            // 如果名称改变，更新所有引用
            if ($name !== $newSub['name']) {
                $this->updateReferences($name, $newSub['name']);
            }
            
            $this->updateByName($allSubs, $name, $newSub);
            $this->app->write(SUBS_KEY, $allSubs);
            
            $res->json([
                'success' => true,
                'data' => $newSub
            ]);
            
            $this->app->info("更新订阅: {$name}");
        } else {
            $this->failed($res, new ResourceNotFoundError(
                'RESOURCE_NOT_FOUND',
                "Subscription {$name} does not exist!"
            ), 404);
        }
    }

    /**
     * 删除订阅
     */
    public function deleteSubscription($req, $res): void
    {
        try {
            $name = $req->params['name'];
            $mode = $req->query['mode'] ?? 'permanent';
            
            $this->app->info("删除订阅: {$name}");
            
            // 归档处理
            if ($mode === 'archive') {
                $this->archiveSubscription($name);
            }
            
            $this->deleteSubscriptionItem($name);
            
            $res->json([
                'success' => true,
                'message' => 'Subscription deleted'
            ]);
            
            $this->app->info("删除订阅成功: {$name}");
        } catch (\Exception $e) {
            $this->failed($res, $e);
        }
    }

    /**
     * 获取所有订阅
     */
    public function getAllSubscriptions($req, $res): void
    {
        $allSubs = $this->app->read(SUBS_KEY) ?? [];
        $res->json([
            'success' => true,
            'data' => $allSubs
        ]);
    }

    /**
     * 替换所有订阅
     */
    public function replaceSubscriptions($req, $res): void
    {
        $allSubs = $req->body;
        $this->app->write(SUBS_KEY, $allSubs);
        
        $res->json([
            'success' => true,
            'message' => 'Subscriptions replaced'
        ]);
    }

    /**
     * 辅助方法：按名称查找订阅
     */
    private function findByName(array $items, string $name): ?array
    {
        foreach ($items as $item) {
            if ($item['name'] === $name) {
                return $item;
            }
        }
        return null;
    }

    /**
     * 辅助方法：按名称更新订阅
     */
    private function updateByName(array &$items, string $oldName, array $newItem): void
    {
        foreach ($items as &$item) {
            if ($item['name'] === $oldName) {
                $item = $newItem;
                break;
            }
        }
    }

    /**
     * 辅助方法：按名称删除订阅
     */
    private function deleteSubscriptionItem(string $name): void
    {
        $allSubs = $this->app->read(SUBS_KEY) ?? [];
        $sub = $this->findByName($allSubs, $name);
        
        if (!$sub) {
            throw new ResourceNotFoundError(
                'RESOURCE_NOT_FOUND',
                "Subscription {$name} does not exist!"
            );
        }
        
        $allSubs = array_filter($allSubs, function($item) use ($name) {
            return $item['name'] !== $name;
        });
        $allSubs = array_values($allSubs);
        
        $this->app->write(SUBS_KEY, $allSubs);
        
        // 清理集合中的引用
        $allCols = $this->app->read(COLLECTIONS_KEY) ?? [];
        foreach ($allCols as &$collection) {
            $collection['subscriptions'] = array_filter($collection['subscriptions'], function($subName) use ($name) {
                return $subName !== $name;
            });
        }
        $this->app->write(COLLECTIONS_KEY, $allCols);
    }

    /**
     * 辅助方法：验证订阅名称
     */
    private function validateSubscriptionName(string $name): void
    {
        if (empty($name)) {
            throw new RequestInvalidError(
                'INVALID_NAME',
                'Subscription name cannot be empty'
            );
        }
        
        if (strpos($name, '/') !== false) {
            throw new RequestInvalidError(
                'INVALID_NAME',
                "Subscription {$name} is invalid (contains /)"
            );
        }
    }

    /**
     * 辅助方法：更新引用
     */
    private function updateReferences(string $oldName, string $newName): void
    {
        // 更新集合中的引用
        $allCols = $this->app->read(COLLECTIONS_KEY) ?? [];
        foreach ($allCols as &$collection) {
            $collection['subscriptions'] = array_map(function($subName) use ($oldName, $newName) {
                return $subName === $oldName ? $newName : $subName;
            }, $collection['subscriptions'] ?? []);
        }
        $this->app->write(COLLECTIONS_KEY, $allCols);

        // 更新产物中的引用
        $allArtifacts = $this->app->read(ARTIFACTS_KEY) ?? [];
        foreach ($allArtifacts as &$artifact) {
            if (($artifact['type'] ?? '') === 'subscription' && ($artifact['source'] ?? '') === $oldName) {
                $artifact['source'] = $newName;
            }
        }
        $this->app->write(ARTIFACTS_KEY, $allArtifacts);

        // 更新文件中的引用
        $allFiles = $this->app->read(FILES_KEY) ?? [];
        foreach ($allFiles as &$file) {
            if (($file['sourceType'] ?? '') === 'subscription' && ($file['sourceName'] ?? '') === $oldName) {
                $file['sourceName'] = $newName;
            }
        }
        $this->app->write(FILES_KEY, $allFiles);
    }

    /**
     * 辅助方法：解析流量头
     */
    private function parseFlowHeaders(string $userInfo): array
    {
        $result = [];
        $pairs = explode(';', $userInfo);
        foreach ($pairs as $pair) {
            $parts = explode('=', trim($pair), 2);
            if (count($parts) === 2) {
                $result[trim($parts[0])] = trim($parts[1]);
            }
        }
        return $result;
    }

    /**
     * 辅助方法：归档订阅
     */
    private function archiveSubscription(string $name): void
    {
        // TODO: 实现归档功能
        $this->app->info("归档订阅: {$name}");
    }

    /**
     * 辅助方法：返回错误响应
     */
    private function failed($res, \Exception $error, ?int $httpCode = null): void
    {
        $statusCode = $httpCode ?? $error->getCode();
        $res->json([
            'success' => false,
            'error' => $error instanceof BaseError ? $error->getErrorType() : 'INTERNAL_ERROR',
            'message' => $error->getMessage()
        ], $statusCode);
    }
}
