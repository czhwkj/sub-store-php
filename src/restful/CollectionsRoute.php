<?php

declare(strict_types=1);

namespace SubStore\Restful;

use SubStore\Core\Router;
use SubStore\Core\App;
use SubStore\Restful\errors as Errors;

class CollectionsRoute
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
        // 确保集合数据存在
        if (!$this->app->read(COLLECTIONS_KEY)) {
            $this->app->write(COLLECTIONS_KEY, []);
        }

        // GET /api/collection/:name - 获取集合详情
        $this->router->get('/api/collection/:name', [$this, 'getCollection']);

        // PATCH /api/collection/:name - 更新集合
        $this->router->patch('/api/collection/:name', [$this, 'updateCollection']);

        // DELETE /api/collection/:name - 删除集合
        $this->router->delete('/api/collection/:name', [$this, 'deleteCollection']);

        // GET /api/collections - 获取所有集合
        $this->router->get('/api/collections', [$this, 'getAllCollections']);

        // POST /api/collections - 创建集合
        $this->router->post('/api/collections', [$this, 'createCollection']);

        // PUT /api/collections - 替换所有集合
        $this->router->put('/api/collections', [$this, 'replaceCollections']);
    }

    public function createCollection($req, $res): void
    {
        try {
            $collection = $req->body;
            $this->validateCollectionName($collection['name'] ?? '');
            
            $allCols = $this->app->read(COLLECTIONS_KEY) ?? [];
            
            if ($this->findByName($allCols, $collection['name'])) {
                throw new Errors\RequestInvalidError(
                    'DUPLICATE_KEY',
                    "Collection {$collection['name']} already exists."
                );
            }

            $collection['createdAt'] = time();
            $collection['updatedAt'] = time();
            
            $allCols[] = $collection;
            $this->app->write(COLLECTIONS_KEY, $allCols);
            
            $res->json([
                'success' => true,
                'data' => $collection
            ], 201);
            
            $this->app->info("创建集合: {$collection['name']}");
        } catch (\Exception $e) {
            $this->failed($res, $e);
        }
    }

    public function getCollection($req, $res): void
    {
        $name = $req->params['name'];
        $allCols = $this->app->read(COLLECTIONS_KEY) ?? [];
        $collection = $this->findByName($allCols, $name);

        if ($collection) {
            $res->json([
                'success' => true,
                'data' => $collection
            ]);
        } else {
            $this->failed($res, new Errors\ResourceNotFoundError(
                'RESOURCE_NOT_FOUND',
                "Collection {$name} does not exist"
            ), 404);
        }
    }

    public function updateCollection($req, $res): void
    {
        $name = $req->params['name'];
        $updateData = $req->body;

        $allCols = $this->app->read(COLLECTIONS_KEY) ?? [];
        $oldCollection = $this->findByName($allCols, $name);

        if ($oldCollection) {
            if (empty($updateData['name'])) {
                $updateData['name'] = $oldCollection['name'];
            }
            
            $newCollection = array_merge($oldCollection, $updateData);
            $newCollection['updatedAt'] = time();
            
            // 如果名称改变，更新所有引用
            if ($name !== $newCollection['name']) {
                $this->updateReferences($name, $newCollection['name']);
            }
            
            $this->updateByName($allCols, $name, $newCollection);
            $this->app->write(COLLECTIONS_KEY, $allCols);
            
            $res->json([
                'success' => true,
                'data' => $newCollection
            ]);
            
            $this->app->info("更新集合: {$name}");
        } else {
            $this->failed($res, new Errors\ResourceNotFoundError(
                'RESOURCE_NOT_FOUND',
                "Collection {$name} does not exist!"
            ), 404);
        }
    }

    public function deleteCollection($req, $res): void
    {
        try {
            $name = $req->params['name'];
            $this->app->info("删除集合: {$name}");
            
            $allCols = $this->app->read(COLLECTIONS_KEY) ?? [];
            $collection = $this->findByName($allCols, $name);
            
            if (!$collection) {
                throw new Errors\ResourceNotFoundError(
                    'RESOURCE_NOT_FOUND',
                    "Collection {$name} does not exist!"
                );
            }
            
            $allCols = array_filter($allCols, function($item) use ($name) {
                return $item['name'] !== $name;
            });
            $allCols = array_values($allCols);
            
            $this->app->write(COLLECTIONS_KEY, $allCols);
            
            $res->json([
                'success' => true,
                'message' => 'Collection deleted'
            ]);
            
            $this->app->info("删除集合成功: {$name}");
        } catch (\Exception $e) {
            $this->failed($res, $e);
        }
    }

    public function getAllCollections($req, $res): void
    {
        $allCols = $this->app->read(COLLECTIONS_KEY) ?? [];
        $res->json([
            'success' => true,
            'data' => $allCols
        ]);
    }

    public function replaceCollections($req, $res): void
    {
        $allCols = $req->body;
        $this->app->write(COLLECTIONS_KEY, $allCols);
        
        $res->json([
            'success' => true,
            'message' => 'Collections replaced'
        ]);
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

    private function validateCollectionName(string $name): void
    {
        if (empty($name)) {
            throw new Errors\RequestInvalidError(
                'INVALID_NAME',
                'Collection name cannot be empty'
            );
        }
        
        if (strpos($name, '/') !== false) {
            throw new Errors\RequestInvalidError(
                'INVALID_NAME',
                "Collection {$name} is invalid (contains /)"
            );
        }
    }

    private function updateReferences(string $oldName, string $newName): void
    {
        // 更新产物中的引用
        $allArtifacts = $this->app->read(ARTIFACTS_KEY) ?? [];
        foreach ($allArtifacts as &$artifact) {
            if (($artifact['type'] ?? '') === 'collection' && ($artifact['source'] ?? '') === $oldName) {
                $artifact['source'] = $newName;
            }
        }
        $this->app->write(ARTIFACTS_KEY, $allArtifacts);
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
