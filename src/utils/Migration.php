<?php

declare(strict_types=1);

namespace SubStore\Utils;

use SubStore\Core\App;

/**
 * 数据迁移工具
 */
class Migration
{
    private App $app;
    
    public function __construct(App $app)
    {
        $this->app = $app;
    }
    
    /**
     * 运行迁移
     */
    public function run(): void
    {
        $currentVersion = $this->app->read(SCHEMA_VERSION_KEY) ?? 0;
        $targetVersion = 1; // 当前最新版本
        
        if ($currentVersion < $targetVersion) {
            $this->app->info("开始数据迁移: v{$currentVersion} -> v{$targetVersion}");
            
            // 执行迁移
            $this->migrateToV1();
            
            // 更新版本号
            $this->app->write(SCHEMA_VERSION_KEY, $targetVersion);
            $this->app->info("数据迁移完成: v{$targetVersion}");
        }
    }
    
    /**
     * 迁移到 V1
     */
    private function migrateToV1(): void
    {
        // 初始化默认设置
        $settings = $this->app->read(SETTINGS_KEY);
        if (!$settings) {
            $defaultSettings = [
                'locale' => 'zh-CN',
                'version' => '1.0.0',
            ];
            $this->app->write(SETTINGS_KEY, $defaultSettings);
        }
        
        // 初始化订阅列表
        if (!$this->app->read(SUBS_KEY)) {
            $this->app->write(SUBS_KEY, []);
        }
        
        // 初始化组合订阅列表
        if (!$this->app->read(COLLECTIONS_KEY)) {
            $this->app->write(COLLECTIONS_KEY, []);
        }
        
        // 初始化产物列表
        if (!$this->app->read(ARTIFACTS_KEY)) {
            $this->app->write(ARTIFACTS_KEY, []);
        }
        
        // 初始化文件列表
        if (!$this->app->read(FILES_KEY)) {
            $this->app->write(FILES_KEY, []);
        }
        
        // 初始化令牌列表
        if (!$this->app->read(TOKENS_KEY)) {
            $this->app->write(TOKENS_KEY, []);
        }
        
        // 初始化归档列表
        if (!$this->app->read(ARCHIVES_KEY)) {
            $this->app->write(ARCHIVES_KEY, []);
        }
    }
}
