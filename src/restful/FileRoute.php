<?php

declare(strict_types=1);

namespace SubStore\Restful;

use SubStore\Core\Router;
use SubStore\Core\App;
use SubStore\Restful\errors as Errors;

class FileRoute
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
        if (!$this->app->read(FILES_KEY)) {
            $this->app->write(FILES_KEY, []);
        }

        // GET /api/file/:path - 获取文件
        $this->router->get('/api/file/:path', [$this, 'getFile']);

        // PUT /api/file/:path - 更新文件
        $this->router->put('/api/file/:path', [$this, 'updateFile']);

        // DELETE /api/file/:path - 删除文件
        $this->router->delete('/api/file/:path', [$this, 'deleteFile']);

        // GET /api/files - 获取所有文件
        $this->router->get('/api/files', [$this, 'getAllFiles']);

        // POST /api/files - 创建文件
        $this->router->post('/api/files', [$this, 'createFile']);
    }

    public function createFile($req, $res): void
    {
        try {
            $file = $req->body;
            $path = $file['path'] ?? '';
            
            if (empty($path)) {
                throw new Errors\RequestInvalidError(
                    'INVALID_PATH',
                    'File path cannot be empty'
                );
            }
            
            $allFiles = $this->app->read(FILES_KEY) ?? [];
            
            if ($this->findByPath($allFiles, $path)) {
                throw new Errors\RequestInvalidError(
                    'DUPLICATE_KEY',
                    "File {$path} already exists."
                );
            }

            $file['createdAt'] = time();
            $file['updatedAt'] = time();
            
            $allFiles[] = $file;
            $this->app->write(FILES_KEY, $allFiles);
            
            $res->json([
                'success' => true,
                'data' => $file
            ], 201);
            
            $this->app->info("创建文件: {$path}");
        } catch (\Exception $e) {
            $this->failed($res, $e);
        }
    }

    public function getFile($req, $res): void
    {
        $path = $req->params['path'];
        $allFiles = $this->app->read(FILES_KEY) ?? [];
        $file = $this->findByPath($allFiles, $path);

        if ($file) {
            $res->json([
                'success' => true,
                'data' => $file
            ]);
        } else {
            $this->failed($res, new Errors\ResourceNotFoundError(
                'RESOURCE_NOT_FOUND',
                "File {$path} does not exist"
            ), 404);
        }
    }

    public function updateFile($req, $res): void
    {
        $path = $req->params['path'];
        $updateData = $req->body;

        $allFiles = $this->app->read(FILES_KEY) ?? [];
        $oldFile = $this->findByPath($allFiles, $path);

        if ($oldFile) {
            $newFile = array_merge($oldFile, $updateData);
            $newFile['updatedAt'] = time();
            
            $this->updateByPath($allFiles, $path, $newFile);
            $this->app->write(FILES_KEY, $allFiles);
            
            $res->json([
                'success' => true,
                'data' => $newFile
            ]);
            
            $this->app->info("更新文件: {$path}");
        } else {
            $this->failed($res, new Errors\ResourceNotFoundError(
                'RESOURCE_NOT_FOUND',
                "File {$path} does not exist!"
            ), 404);
        }
    }

    public function deleteFile($req, $res): void
    {
        try {
            $path = $req->params['path'];
            $this->app->info("删除文件: {$path}");
            
            $allFiles = $this->app->read(FILES_KEY) ?? [];
            $file = $this->findByPath($allFiles, $path);
            
            if (!$file) {
                throw new Errors\ResourceNotFoundError(
                    'RESOURCE_NOT_FOUND',
                    "File {$path} does not exist!"
                );
            }
            
            $allFiles = array_filter($allFiles, function($item) use ($path) {
                return $item['path'] !== $path;
            });
            $allFiles = array_values($allFiles);
            
            $this->app->write(FILES_KEY, $allFiles);
            
            $res->json([
                'success' => true,
                'message' => 'File deleted'
            ]);
            
            $this->app->info("删除文件成功: {$path}");
        } catch (\Exception $e) {
            $this->failed($res, $e);
        }
    }

    public function getAllFiles($req, $res): void
    {
        $allFiles = $this->app->read(FILES_KEY) ?? [];
        $res->json([
            'success' => true,
            'data' => $allFiles
        ]);
    }

    private function findByPath(array $items, string $path): ?array
    {
        foreach ($items as $item) {
            if ($item['path'] === $path) {
                return $item;
            }
        }
        return null;
    }

    private function updateByPath(array &$items, string $oldPath, array $newItem): void
    {
        foreach ($items as &$item) {
            if ($item['path'] === $oldPath) {
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
