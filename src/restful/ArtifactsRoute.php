<?php

declare(strict_types=1);

namespace SubStore\Restful;

use SubStore\Core\Router;
use SubStore\Core\App;
use SubStore\Restful\errors as Errors;

class ArtifactsRoute
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
        if (!$this->app->read(ARTIFACTS_KEY)) {
            $this->app->write(ARTIFACTS_KEY, []);
        }

        // GET /api/artifacts/restore - 恢复产物
        $this->router->get('/api/artifacts/restore', [$this, 'restoreArtifacts']);

        // GET /api/artifacts - 获取所有产物
        $this->router->get('/api/artifacts', [$this, 'getAllArtifacts']);

        // POST /api/artifacts - 创建产物
        $this->router->post('/api/artifacts', [$this, 'createArtifact']);

        // PUT /api/artifacts - 替换所有产物
        $this->router->put('/api/artifacts', [$this, 'replaceArtifact']);

        // GET /api/artifact/:name - 获取产物详情
        $this->router->get('/api/artifact/:name', [$this, 'getArtifact']);

        // PATCH /api/artifact/:name - 更新产物
        $this->router->patch('/api/artifact/:name', [$this, 'updateArtifact']);

        // DELETE /api/artifact/:name - 删除产物
        $this->router->delete('/api/artifact/:name', [$this, 'deleteArtifact']);
    }

    public function createArtifact($req, $res): void
    {
        try {
            $artifact = $req->body;
            $this->validateArtifactName($artifact['name'] ?? '');
            
            $allArtifacts = $this->app->read(ARTIFACTS_KEY) ?? [];
            
            if ($this->findByName($allArtifacts, $artifact['name'])) {
                throw new Errors\RequestInvalidError(
                    'DUPLICATE_KEY',
                    "Artifact {$artifact['name']} already exists."
                );
            }

            $artifact['createdAt'] = time();
            $artifact['updatedAt'] = time();
            
            $allArtifacts[] = $artifact;
            $this->app->write(ARTIFACTS_KEY, $allArtifacts);
            
            $res->json([
                'success' => true,
                'data' => $artifact
            ], 201);
            
            $this->app->info("创建产物: {$artifact['name']}");
        } catch (\Exception $e) {
            $this->failed($res, $e);
        }
    }

    public function getArtifact($req, $res): void
    {
        $name = $req->params['name'];
        $allArtifacts = $this->app->read(ARTIFACTS_KEY) ?? [];
        $artifact = $this->findByName($allArtifacts, $name);

        if ($artifact) {
            $res->json([
                'success' => true,
                'data' => $artifact
            ]);
        } else {
            $this->failed($res, new Errors\ResourceNotFoundError(
                'RESOURCE_NOT_FOUND',
                "Artifact {$name} does not exist!"
            ), 404);
        }
    }

    public function updateArtifact($req, $res): void
    {
        $name = $req->params['name'];
        $updateData = $req->body;

        $allArtifacts = $this->app->read(ARTIFACTS_KEY) ?? [];
        $oldArtifact = $this->findByName($allArtifacts, $name);

        if ($oldArtifact) {
            if (empty($updateData['name'])) {
                $updateData['name'] = $oldArtifact['name'];
            }
            
            $newArtifact = array_merge($oldArtifact, $updateData);
            $newArtifact['updatedAt'] = time();
            
            $this->updateByName($allArtifacts, $name, $newArtifact);
            $this->app->write(ARTIFACTS_KEY, $allArtifacts);
            
            $res->json([
                'success' => true,
                'data' => $newArtifact
            ]);
            
            $this->app->info("更新产物: {$name}");
        } else {
            $this->failed($res, new Errors\RequestInvalidError(
                'DUPLICATE_KEY',
                "Artifact {$name} already exists."
            ));
        }
    }

    public function deleteArtifact($req, $res): void
    {
        try {
            $name = $req->params['name'];
            $this->app->info("正在删除产物: {$name}");
            
            $allArtifacts = $this->app->read(ARTIFACTS_KEY) ?? [];
            $artifact = $this->findByName($allArtifacts, $name);
            
            if (!$artifact) {
                throw new Errors\ResourceNotFoundError(
                    'RESOURCE_NOT_FOUND',
                    "Artifact {$name} does not exist!"
                );
            }
            
            $allArtifacts = array_filter($allArtifacts, function($item) use ($name) {
                return $item['name'] !== $name;
            });
            $allArtifacts = array_values($allArtifacts);
            
            $this->app->write(ARTIFACTS_KEY, $allArtifacts);
            
            $res->json([
                'success' => true,
                'message' => 'Artifact deleted'
            ]);
            
            $this->app->info("删除产物成功: {$name}");
        } catch (\Exception $e) {
            $this->failed($res, $e);
        }
    }

    public function getAllArtifacts($req, $res): void
    {
        $allArtifacts = $this->app->read(ARTIFACTS_KEY) ?? [];
        $res->json([
            'success' => true,
            'data' => $allArtifacts
        ]);
    }

    public function replaceArtifact($req, $res): void
    {
        $allArtifacts = $req->body;
        $this->app->write(ARTIFACTS_KEY, $allArtifacts);
        
        $res->json([
            'success' => true,
            'message' => 'Artifacts replaced'
        ]);
    }

    public function restoreArtifacts($req, $res): void
    {
        $this->app->info('开始恢复远程配置...');
        
        $settings = $this->app->read(SETTINGS_KEY) ?? [];
        $gistToken = $settings['gistToken'] ?? null;
        
        if (!$gistToken) {
            $this->failed($res, new Errors\InternalServerError(
                'NO_GIST_TOKEN',
                '未设置 GitHub Token！'
            ));
            return;
        }
        
        // TODO: 实现从 Gist 恢复功能
        $this->failed($res, new Errors\InternalServerError(
            'NOT_IMPLEMENTED',
            'Gist restore not implemented yet'
        ));
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

    private function validateArtifactName(string $name): void
    {
        if (!preg_match('/^[a-zA-Z0-9._-]*$/', $name)) {
            throw new Errors\RequestInvalidError(
                'INVALID_ARTIFACT_NAME',
                "Artifact name {$name} is invalid."
            );
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
