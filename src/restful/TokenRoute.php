<?php

declare(strict_types=1);

namespace SubStore\Restful;

use SubStore\Core\Router;
use SubStore\Core\App;
use SubStore\Restful\errors as Errors;

class TokenRoute
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
        if (!$this->app->read(TOKENS_KEY)) {
            $this->app->write(TOKENS_KEY, []);
        }

        // GET /api/token/:token - 获取 Token
        $this->router->get('/api/token/:token', [$this, 'getToken']);

        // PUT /api/token/:token - 更新 Token
        $this->router->put('/api/token/:token', [$this, 'updateToken']);

        // DELETE /api/token/:token - 删除 Token
        $this->router->delete('/api/token/:token', [$this, 'deleteToken']);

        // GET /api/tokens - 获取所有 Token
        $this->router->get('/api/tokens', [$this, 'getAllTokens']);

        // POST /api/tokens - 创建 Token
        $this->router->post('/api/tokens', [$this, 'createToken']);
    }

    public function createToken($req, $res): void
    {
        try {
            $tokenData = $req->body;
            
            if (empty($tokenData['token']) || empty($tokenData['type']) || empty($tokenData['name'])) {
                throw new Errors\RequestInvalidError(
                    'INVALID_TOKEN',
                    'Token, type and name are required'
                );
            }
            
            $allTokens = $this->app->read(TOKENS_KEY) ?? [];
            
            // 检查是否已存在
            foreach ($allTokens as $t) {
                if ($t['token'] === $tokenData['token']) {
                    throw new Errors\RequestInvalidError(
                        'DUPLICATE_KEY',
                        "Token {$tokenData['token']} already exists."
                    );
                }
            }

            $tokenData['createdAt'] = time();
            
            $allTokens[] = $tokenData;
            $this->app->write(TOKENS_KEY, $allTokens);
            
            $res->json([
                'success' => true,
                'data' => $tokenData
            ], 201);
            
            $this->app->info("创建 Token");
        } catch (\Exception $e) {
            $this->failed($res, $e);
        }
    }

    public function getToken($req, $res): void
    {
        $token = $req->params['token'];
        $allTokens = $this->app->read(TOKENS_KEY) ?? [];
        
        $found = null;
        foreach ($allTokens as $t) {
            if ($t['token'] === $token) {
                $found = $t;
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
                "Token not found"
            ), 404);
        }
    }

    public function updateToken($req, $res): void
    {
        $token = $req->params['token'];
        $updateData = $req->body;

        $allTokens = $this->app->read(TOKENS_KEY) ?? [];
        
        foreach ($allTokens as &$t) {
            if ($t['token'] === $token) {
                $t = array_merge($t, $updateData);
                $this->app->write(TOKENS_KEY, $allTokens);
                
                $res->json([
                    'success' => true,
                    'data' => $t
                ]);
                
                $this->app->info("更新 Token");
                return;
            }
        }
        
        $this->failed($res, new Errors\ResourceNotFoundError(
            'RESOURCE_NOT_FOUND',
            "Token not found!"
        ), 404);
    }

    public function deleteToken($req, $res): void
    {
        try {
            $token = $req->params['token'];
            $this->app->info("删除 Token: {$token}");
            
            $allTokens = $this->app->read(TOKENS_KEY) ?? [];
            
            $filtered = array_filter($allTokens, function($t) use ($token) {
                return $t['token'] !== $token;
            });
            $filtered = array_values($filtered);
            
            if (count($filtered) === count($allTokens)) {
                throw new Errors\ResourceNotFoundError(
                    'RESOURCE_NOT_FOUND',
                    "Token not found!"
                );
            }
            
            $this->app->write(TOKENS_KEY, $filtered);
            
            $res->json([
                'success' => true,
                'message' => 'Token deleted'
            ]);
            
            $this->app->info("删除 Token 成功");
        } catch (\Exception $e) {
            $this->failed($res, $e);
        }
    }

    public function getAllTokens($req, $res): void
    {
        $allTokens = $this->app->read(TOKENS_KEY) ?? [];
        $res->json([
            'success' => true,
            'data' => $allTokens
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
