<?php

declare(strict_types=1);

namespace SubStore\Core\ProxyUtils;

/**
 * 代理工具类
 * 用于统一格式化和标准化代理信息
 */
class ProxyUtils
{
    /**
     * 标准化代理对象
     */
    public static function standardizeProxy(array $proxy): array
    {
        $standardized = [
            'name' => $proxy['name'] ?? '',
            'type' => $proxy['type'] ?? 'unknown',
            'server' => $proxy['server'] ?? '',
            'port' => $proxy['port'] ?? 0,
            'udp' => $proxy['udp'] ?? false,
        ];

        // 根据类型添加额外字段
        switch ($standardized['type']) {
            case 'ss':
            case 'shadowsocks':
                $standardized['type'] = 'ss';
                $standardized['cipher'] = $proxy['cipher'] ?? '';
                $standardized['password'] = $proxy['password'] ?? '';
                break;

            case 'vmess':
                $standardized['uuid'] = $proxy['uuid'] ?? '';
                $standardized['alterId'] = $proxy['alterId'] ?? 0;
                $standardized['network'] = $proxy['network'] ?? 'tcp';
                $standardized['tls'] = $proxy['tls'] ?? false;
                break;

            case 'vless':
                $standardized['uuid'] = $proxy['uuid'] ?? '';
                $standardized['network'] = $proxy['network'] ?? 'tcp';
                $standardized['tls'] = $proxy['tls'] ?? false;
                break;

            case 'trojan':
                $standardized['password'] = $proxy['password'] ?? '';
                $standardized['sni'] = $proxy['sni'] ?? '';
                break;

            case 'socks5':
            case 'socks':
                $standardized['type'] = 'socks5';
                $standardized['username'] = $proxy['username'] ?? '';
                $standardized['password'] = $proxy['password'] ?? '';
                $standardized['tls'] = $proxy['tls'] ?? false;
                break;

            case 'http':
            case 'https':
                $standardized['type'] = $proxy['tls'] ?? false ? 'https' : 'http';
                $standardized['username'] = $proxy['username'] ?? '';
                $standardized['password'] = $proxy['password'] ?? '';
                $standardized['tls'] = $proxy['tls'] ?? false;
                break;
        }

        return $standardized;
    }

    /**
     * 生成唯一标识符
     */
    public static function generateUniqueId(array $proxy): string
    {
        return md5(json_encode([
            'type' => $proxy['type'],
            'server' => $proxy['server'],
            'port' => $proxy['port'],
            'name' => $proxy['name'] ?? '',
        ]));
    }

    /**
     * 去重代理
     */
    public static function deduplicate(array $proxies): array
    {
        $seen = [];
        $deduplicated = [];

        foreach ($proxies as $proxy) {
            $id = self::generateUniqueId($proxy);
            if (!in_array($id, $seen)) {
                $seen[] = $id;
                $deduplicated[] = $proxy;
            }
        }

        return $deduplicated;
    }

    /**
     * 过滤代理
     */
    public static function filter(array $proxies, callable $filter): array
    {
        return array_filter($proxies, $filter);
    }

    /**
     * 排序代理
     */
    public static function sort(array $proxies, string $field = 'name', string $direction = 'asc'): array
    {
        usort($proxies, function($a, $b) use ($field, $direction) {
            $valueA = $a[$field] ?? '';
            $valueB = $b[$field] ?? '';

            $result = strcmp((string)$valueA, (string)$valueB);
            return $direction === 'desc' ? -$result : $result;
        });

        return $proxies;
    }
}
