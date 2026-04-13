<?php

declare(strict_types=1);

namespace SubStore\Restful;

use SubStore\Core\Router;
use SubStore\Core\App;

class SettingsRoute
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
        // GET /api/settings - 获取设置
        $this->router->get('/api/settings', [$this, 'getSettings']);

        // PUT /api/settings - 更新设置
        $this->router->put('/api/settings', [$this, 'updateSettings']);
    }

    public function getSettings($req, $res): void
    {
        $settings = $this->app->read(SETTINGS_KEY) ?? [];
        $res->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    public function updateSettings($req, $res): void
    {
        $settings = $req->body;
        $this->app->write(SETTINGS_KEY, $settings);
        
        $res->json([
            'success' => true,
            'data' => $settings
        ]);
        
        $this->app->info('设置已更新');
    }
}
