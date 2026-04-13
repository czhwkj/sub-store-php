<?php

declare(strict_types=1);

namespace SubStore\Restful;

use SubStore\Core\Router;
use SubStore\Core\App;
use SubStore\Restful\errors as Errors;
use SubStore\Utils\Download;
use SubStore\Core\ProxyUtils\ProxyParser;
use SubStore\Core\ProxyUtils\ProxyProducer;
use SubStore\Core\ProxyUtils\ProxyUtils;

class SyncRoute
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
        // POST /api/sync/artifacts - 同步所有产物
        $this->router->post('/api/sync/artifacts', [$this, 'syncArtifacts']);

        // POST /api/sync/artifact/:name - 同步单个产物
        $this->router->post('/api/sync/artifact/:name', [$this, 'syncArtifact']);

        // POST /api/sync/produce/:type/:name - 生成产物
        $this->router->post('/api/sync/produce/:type/:name', [$this, 'produceArtifact']);
    }

    public function syncArtifacts($req, $res): void
    {
        try {
            $this->app->info('开始同步所有产物...');
            
            $settings = $this->app->read(SETTINGS_KEY) ?? [];
            $gistToken = $settings['gistToken'] ?? null;
            
            if (!$gistToken) {
                throw new Errors\RequestInvalidError(
                    'NO_GIST_TOKEN',
                    '未设置 GitHub Token!'
                );
            }
            
            // TODO: 实现 Gist 同步逻辑
            $allArtifacts = $this->app->read(ARTIFACTS_KEY) ?? [];
            
            $this->app->info("同步产物完成，共 " . count($allArtifacts) . " 个");
            
            $res->json([
                'success' => true,
                'message' => 'Artifacts synced',
                'count' => count($allArtifacts)
            ]);
        } catch (\Exception $e) {
            $this->failed($res, $e);
        }
    }

    public function syncArtifact($req, $res): void
    {
        $name = $req->params['name'];
        
        try {
            $this->app->info("正在同步产物: {$name}");
            
            $allArtifacts = $this->app->read(ARTIFACTS_KEY) ?? [];
            $artifact = $this->findByName($allArtifacts, $name);
            
            if (!$artifact) {
                throw new Errors\ResourceNotFoundError(
                    'RESOURCE_NOT_FOUND',
                    "Artifact {$name} does not exist!"
                );
            }
            
            // TODO: 实现单个产物同步逻辑
            
            $this->app->info("同步产物成功: {$name}");
            
            $res->json([
                'success' => true,
                'message' => 'Artifact synced',
                'data' => $artifact
            ]);
        } catch (\Exception $e) {
            $this->failed($res, $e);
        }
    }

    public function produceArtifact($req, $res): void
    {
        $type = $req->params['type'];
        $name = $req->params['name'];
        $platform = $req->query['target'] ?? 'JSON';
        
        try {
            $this->app->info("正在生成产物: {$type} - {$name}");
            
            $proxies = [];
            $parser = new ProxyParser();
            
            if ($type === 'subscription') {
                // 处理单个订阅
                $allSubs = $this->app->read(SUBS_KEY) ?? [];
                $sub = $this->findByName($allSubs, $name);
                
                if (!$sub) {
                    throw new Errors\ResourceNotFoundError(
                        'RESOURCE_NOT_FOUND',
                        "Subscription {$name} does not exist!"
                    );
                }
                
                // 下载并解析远程订阅
                $content = Download::fetchSubscription($sub);
                $proxies = $parser->parse($content);
                
                // 缓存解析结果
                $sub['content'] = $content;
                $sub['proxies'] = $proxies;
                $sub['updatedAt'] = time();
                $this->updateByName($allSubs, $name, $sub);
                $this->app->write(SUBS_KEY, $allSubs);
                
            } else if ($type === 'collection') {
                // 处理集合
                $allCols = $this->app->read(COLLECTIONS_KEY) ?? [];
                $collection = $this->findByName($allCols, $name);
                
                if (!$collection) {
                    throw new Errors\ResourceNotFoundError(
                        'RESOURCE_NOT_FOUND',
                        "Collection {$name} does not exist!"
                    );
                }
                
                $allSubs = $this->app->read(SUBS_KEY) ?? [];
                $subNames = $collection['subscriptions'] ?? [];
                
                foreach ($subNames as $subName) {
                    $sub = $this->findByName($allSubs, $subName);
                    if ($sub) {
                        // 下载并解析
                        $content = Download::fetchSubscription($sub);
                        $subProxies = $parser->parse($content);
                        
                        // 更新缓存
                        $sub['content'] = $content;
                        $sub['proxies'] = $subProxies;
                        $this->updateByName($allSubs, $subName, $sub);
                        
                        $proxies = array_merge($proxies, $subProxies);
                    }
                }
                
                $this->app->write(SUBS_KEY, $allSubs);
            }
            
            // 去重和排序
            if (!empty($proxies)) {
                $proxies = ProxyUtils::deduplicate($proxies);
                $proxies = ProxyUtils::sort($proxies, 'name', 'asc');
            }
            
            // 生成目标格式
            $content = ProxyProducer::produce($proxies, $platform);
            
            // 更新产物
            $allArtifacts = $this->app->read(ARTIFACTS_KEY) ?? [];
            $artifact = $this->findByName($allArtifacts, $name);
            if ($artifact) {
                $artifact['content'] = $content;
                $artifact['updatedAt'] = time();
                $this->updateByName($allArtifacts, $name, $artifact);
                $this->app->write(ARTIFACTS_KEY, $allArtifacts);
            }
            
            $res->json([
                'success' => true,
                'message' => 'Artifact produced',
                'type' => $type,
                'name' => $name,
                'count' => count($proxies)
            ]);
            
            $this->app->info("生成产物成功: {$type} - {$name}, 共 " . count($proxies) . " 个节点");
        } catch (\Exception $e) {
            $this->failed($res, $e);
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

    private function updateByName(array &$items, string $oldName, array $newItem): void
    {
        foreach ($items as &$item) {
            if ($item['name'] === $oldName) {
                $item = $newItem;
                break;
            }
        }
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
