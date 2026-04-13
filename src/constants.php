<?php

declare(strict_types=1);

/**
 * 常量定义
 * 对应原版的 constants.js
 */

// 数据键名
const SCHEMA_VERSION_KEY = 'schemaVersion';
const SETTINGS_KEY = 'settings';
const SUBS_KEY = 'subs';
const COLLECTIONS_KEY = 'collections';
const FILES_KEY = 'files';
const MODULES_KEY = 'modules';
const ARTIFACTS_KEY = 'artifacts';
const RULES_KEY = 'rules';
const TOKENS_KEY = 'tokens';
const ARCHIVES_KEY = 'archives';

// Gist 相关
const GIST_BACKUP_KEY = 'Auto Generated Sub-Store Backup';
const GIST_BACKUP_FILE_NAME = 'Sub-Store';
const ARTIFACT_REPOSITORY_KEY = 'Sub-Store Artifacts Repository';

// 缓存相关
const RESOURCE_CACHE_KEY = '#sub-store-cached-resource';
const HEADERS_RESOURCE_CACHE_KEY = '#sub-store-cached-headers-resource';
const SCRIPT_RESOURCE_CACHE_KEY = '#sub-store-cached-script-resource';

// 缓存时间（秒）
const DEFAULT_CACHE_TTL = 3600; // 1 小时
const DEFAULT_HEADERS_CACHE_TTL = 60; // 1 分钟
const DEFAULT_SCRIPT_CACHE_TTL = 172800; // 48 小时
