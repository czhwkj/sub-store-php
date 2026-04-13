<?php

declare(strict_types=1);

namespace SubStore\Restful;

use SubStore\Core\Router;
use SubStore\Core\App;
use SubStore\Restful\errors as Errors;

class ModuleRoute
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
        if (!$this->app->read(MODULES_KEY)) {
            $this->app->write(MODULES_KEY, []);
        }

        // GET /api/module/:name - 获取模块详情
        $this->router->get('/api/module/:name', [$this, 'getModule']);

        // PATCH /api/module/:name - 更新模块
        $this->router->patch('/api/module/:name', [$this, 'updateModule']);

        // DELETE /api/module/:name - 删除模块
        $this->router->delete('/api/module/:name', [$this, 'deleteModule']);

        // GET /api/modules - 获取所有模块
        $this->router->get('/api/modules', [$this, 'getAllModules']);

        // POST /api/modules - 创建模块
        $this->router->post('/api/modules', [$this, 'createModule']);

        // PUT /api/modules - 替换所有模块
        $this->router->put('/api/modules', [$this, 'replaceModules']);
    }

    public function createModule($req, $res): void
    {
        try {
            $module = $req->body;
            
            if (empty($module['name'])) {
                throw new Errors\RequestInvalidError(
                    'INVALID_NAME',
                    'Module name cannot be empty'
                );
            }
            
            $allModules = $this->app->read(MODULES_KEY) ?? [];
            
            if ($this->findByName($allModules, $module['name'])) {
                throw new Errors\RequestInvalidError(
                    'DUPLICATE_KEY',
                    "Module {$module['name']} already exists."
                );
            }

            $module['createdAt'] = time();
            $module['updatedAt'] = time();
            
            $allModules[] = $module;
            $this->app->write(MODULES_KEY, $allModules);
            
            $res->json([
                'success' => true,
                'data' => $module
            ], 201);
            
            $this->app->info("创建模块: {$module['name']}");
        } catch (\Exception $e) {
            $this->failed($res, $e);
        }
    }

    public function getModule($req, $res): void
    {
        $name = $req->params['name'];
        $allModules = $this->app->read(MODULES_KEY) ?? [];
        $module = $this->findByName($allModules, $name);

        if ($module) {
            $res->json([
                'success' => true,
                'data' => $module
            ]);
        } else {
            $this->failed($res, new Errors\ResourceNotFoundError(
                'RESOURCE_NOT_FOUND',
                "Module {$name} does not exist"
            ), 404);
        }
    }

    public function updateModule($req, $res): void
    {
        $name = $req->params['name'];
        $updateData = $req->body;

        $allModules = $this->app->read(MODULES_KEY) ?? [];
        $oldModule = $this->findByName($allModules, $name);

        if ($oldModule) {
            $newModule = array_merge($oldModule, $updateData);
            $newModule['updatedAt'] = time();
            
            $this->updateByName($allModules, $name, $newModule);
            $this->app->write(MODULES_KEY, $allModules);
            
            $res->json([
                'success' => true,
                'data' => $newModule
            ]);
            
            $this->app->info("更新模块: {$name}");
        } else {
            $this->failed($res, new Errors\ResourceNotFoundError(
                'RESOURCE_NOT_FOUND',
                "Module {$name} does not exist!"
            ), 404);
        }
    }

    public function deleteModule($req, $res): void
    {
        try {
            $name = $req->params['name'];
            $this->app->info("删除模块: {$name}");
            
            $allModules = $this->app->read(MODULES_KEY) ?? [];
            $module = $this->findByName($allModules, $name);
            
            if (!$module) {
                throw new Errors\ResourceNotFoundError(
                    'RESOURCE_NOT_FOUND',
                    "Module {$name} does not exist!"
                );
            }
            
            $allModules = array_filter($allModules, function($item) use ($name) {
                return $item['name'] !== $name;
            });
            $allModules = array_values($allModules);
            
            $this->app->write(MODULES_KEY, $allModules);
            
            $res->json([
                'success' => true,
                'message' => 'Module deleted'
            ]);
            
            $this->app->info("删除模块成功: {$name}");
        } catch (\Exception $e) {
            $this->failed($res, $e);
        }
    }

    public function getAllModules($req, $res): void
    {
        $allModules = $this->app->read(MODULES_KEY) ?? [];
        $res->json([
            'success' => true,
            'data' => $allModules
        ]);
    }

    public function replaceModules($req, $res): void
    {
        $allModules = $req->body;
        $this->app->write(MODULES_KEY, $allModules);
        
        $res->json([
            'success' => true,
            'message' => 'Modules replaced'
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
