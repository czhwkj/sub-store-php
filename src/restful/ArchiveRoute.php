<?php

declare(strict_types=1);

namespace SubStore\Restful;

use SubStore\Core\Router;
use SubStore\Core\App;
use SubStore\Restful\errors as Errors;

class ArchiveRoute
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
        if (!$this->app->read(ARCHIVES_KEY)) {
            $this->app->write(ARCHIVES_KEY, []);
        }

        // GET /api/archives - 获取所有归档
        $this->router->get('/api/archives', [$this, 'getAllArchives']);

        // POST /api/archives - 创建归档
        $this->router->post('/api/archives', [$this, 'createArchive']);

        // GET /api/archive/:id - 获取归档详情
        $this->router->get('/api/archive/:id', [$this, 'getArchive']);

        // DELETE /api/archive/:id - 删除归档
        $this->router->delete('/api/archive/:id', [$this, 'deleteArchive']);
    }

    public function createArchive($req, $res): void
    {
        try {
            $archive = $req->body;
            
            $allArchives = $this->app->read(ARCHIVES_KEY) ?? [];
            
            $archive['id'] = uniqid();
            $archive['createdAt'] = time();
            
            $allArchives[] = $archive;
            $this->app->write(ARCHIVES_KEY, $allArchives);
            
            $res->json([
                'success' => true,
                'data' => $archive
            ], 201);
            
            $this->app->info("创建归档");
        } catch (\Exception $e) {
            $this->failed($res, $e);
        }
    }

    public function getArchive($req, $res): void
    {
        $id = $req->params['id'];
        $allArchives = $this->app->read(ARCHIVES_KEY) ?? [];
        
        $found = null;
        foreach ($allArchives as $archive) {
            if ($archive['id'] === $id) {
                $found = $archive;
                break;
            }
        }

        if ($found) {
            $res->json([
                'success' => true,
                'data' => $found
            ]);
        } else {
            $this->failed($res, new Errors\ResourceNotFoundError(
                'RESOURCE_NOT_FOUND',
                "Archive not found"
            ), 404);
        }
    }

    public function deleteArchive($req, $res): void
    {
        try {
            $id = $req->params['id'];
            $this->app->info("删除归档: {$id}");
            
            $allArchives = $this->app->read(ARCHIVES_KEY) ?? [];
            
            $filtered = array_filter($allArchives, function($archive) use ($id) {
                return $archive['id'] !== $id;
            });
            $filtered = array_values($filtered);
            
            if (count($filtered) === count($allArchives)) {
                throw new Errors\ResourceNotFoundError(
                    'RESOURCE_NOT_FOUND',
                    "Archive not found!"
                );
            }
            
            $this->app->write(ARCHIVES_KEY, $filtered);
            
            $res->json([
                'success' => true,
                'message' => 'Archive deleted'
            ]);
            
            $this->app->info("删除归档成功");
        } catch (\Exception $e) {
            $this->failed($res, $e);
        }
    }

    public function getAllArchives($req, $res): void
    {
        $allArchives = $this->app->read(ARCHIVES_KEY) ?? [];
        $res->json([
            'success' => true,
            'data' => $allArchives
        ]);
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
