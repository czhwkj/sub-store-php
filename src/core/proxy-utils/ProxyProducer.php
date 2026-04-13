<?php

declare(strict_types=1);

namespace SubStore\Core\ProxyUtils;

/**
 * 代理生成器
 * 将代理转换为不同平台的格式
 */
class ProxyProducer
{
    /**
     * 生成指定格式的代理列表
     */
    public static function produce(array $proxies, string $platform = 'JSON'): string
    {
        switch (strtolower($platform)) {
            case 'json':
                return self::produceJSON($proxies);
            
            case 'clash':
            case 'mihomo':
            case 'clashmeta':
                return self::produceClash($proxies);
            
            case 'surge':
                return self::produceSurge($proxies);
            
            case 'loon':
                return self::produceLoon($proxies);
            
            case 'qx':
            case 'quantumultx':
                return self::produceQX($proxies);
            
            case 'v2ray':
            case 'v2':
                return self::produceV2Ray($proxies);
            
            case 'singbox':
            case 'sing-box':
                return self::produceSingBox($proxies);
            
            case 'stash':
                return self::produceStash($proxies);
            
            default:
                return self::produceJSON($proxies);
        }
    }

    /**
     * 生成 JSON 格式
     */
    private static function produceJSON(array $proxies): string
    {
        return json_encode($proxies, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * 生成 Clash 格式
     */
    private static function produceClash(array $proxies): string
    {
        $yaml = "proxies:\n";

        foreach ($proxies as $proxy) {
            $yaml .= self::proxyToClashYaml($proxy);
        }

        return $yaml;
    }

    /**
     * 将单个代理转换为 Clash YAML
     */
    private static function proxyToClashYaml(array $proxy): string
    {
        $yaml = "  - name: \"{$proxy['name']}\"\n";
        $yaml .= "    type: {$proxy['type']}\n";
        $yaml .= "    server: {$proxy['server']}\n";
        $yaml .= "    port: {$proxy['port']}\n";

        switch ($proxy['type']) {
            case 'ss':
                $yaml .= "    cipher: {$proxy['cipher']}\n";
                $yaml .= "    password: \"{$proxy['password']}\"\n";
                break;

            case 'vmess':
                $yaml .= "    uuid: {$proxy['uuid']}\n";
                $yaml .= "    alterId: {$proxy['alterId']}\n";
                $yaml .= "    cipher: auto\n";
                if (isset($proxy['network']) && $proxy['network'] !== 'tcp') {
                    $yaml .= "    network: {$proxy['network']}\n";
                }
                if ($proxy['tls'] ?? false) {
                    $yaml .= "    tls: true\n";
                }
                break;

            case 'vless':
                $yaml .= "    uuid: {$proxy['uuid']}\n";
                if (isset($proxy['network']) && $proxy['network'] !== 'tcp') {
                    $yaml .= "    network: {$proxy['network']}\n";
                }
                if ($proxy['tls'] ?? false) {
                    $yaml .= "    tls: true\n";
                }
                break;

            case 'trojan':
                $yaml .= "    password: \"{$proxy['password']}\"\n";
                if (isset($proxy['sni']) && !empty($proxy['sni'])) {
                    $yaml .= "    sni: {$proxy['sni']}\n";
                }
                break;

            case 'socks5':
                if (isset($proxy['username']) && !empty($proxy['username'])) {
                    $yaml .= "    username: \"{$proxy['username']}\"\n";
                    $yaml .= "    password: \"{$proxy['password']}\"\n";
                }
                break;
        }

        $yaml .= "\n";
        return $yaml;
    }

    /**
     * 生成 Surge 格式
     */
    private static function produceSurge(array $proxies): string
    {
        $lines = [];

        foreach ($proxies as $proxy) {
            $line = self::proxyToSurge($proxy);
            if ($line !== null) {
                $lines[] = $line;
            }
        }

        return implode("\n", $lines);
    }

    /**
     * 将单个代理转换为 Surge 格式
     */
    private static function proxyToSurge(array $proxy): ?string
    {
        switch ($proxy['type']) {
            case 'ss':
                return "{$proxy['name']} = ss, {$proxy['server']}, {$proxy['port']}, encrypt-method={$proxy['cipher']}, password={$proxy['password']}";
            
            case 'vmess':
                $line = "{$proxy['name']} = vmess, {$proxy['server']}, {$proxy['port']}, username={$proxy['uuid']}";
                if ($proxy['tls'] ?? false) {
                    $line .= ", tls=true";
                }
                if (isset($proxy['network']) && $proxy['network'] !== 'tcp') {
                    $line .= ", ws=true";
                }
                return $line;
            
            case 'trojan':
                $line = "{$proxy['name']} = trojan, {$proxy['server']}, {$proxy['port']}, password={$proxy['password']}";
                if (isset($proxy['sni']) && !empty($proxy['sni'])) {
                    $line .= ", sni={$proxy['sni']}";
                }
                return $line;
            
            case 'http':
            case 'https':
                $line = "{$proxy['name']} = http, {$proxy['server']}, {$proxy['port']}";
                if (isset($proxy['username']) && !empty($proxy['username'])) {
                    $line .= ", username={$proxy['username']}, password={$proxy['password']}";
                }
                if ($proxy['type'] === 'https') {
                    $line .= ", tls=true";
                }
                return $line;
            
            default:
                return null;
        }
    }

    /**
     * 生成 Loon 格式
     */
    private static function produceLoon(array $proxies): string
    {
        $lines = [];

        foreach ($proxies as $proxy) {
            $line = self::proxyToLoon($proxy);
            if ($line !== null) {
                $lines[] = $line;
            }
        }

        return implode("\n", $lines);
    }

    /**
     * 将单个代理转换为 Loon 格式
     */
    private static function proxyToLoon(array $proxy): ?string
    {
        switch ($proxy['type']) {
            case 'ss':
                return "Shadowsocks, \"{$proxy['name']}\", \"{$proxy['server']}\", {$proxy['port']}, \"{$proxy['cipher']}\", \"{$proxy['password']}\"";
            
            case 'vmess':
                $line = "Vmess, \"{$proxy['name']}\", \"{$proxy['server']}\", {$proxy['port']}, \"{$proxy['uuid']}\"";
                if ($proxy['tls'] ?? false) {
                    $line .= ", tls=true";
                }
                return $line;
            
            case 'trojan':
                return "Trojan, \"{$proxy['name']}\", \"{$proxy['server']}\", {$proxy['port']}, \"{$proxy['password']}\"";
            
            default:
                return null;
        }
    }

    /**
     * 生成 Quantumult X 格式
     */
    private static function produceQX(array $proxies): string
    {
        $lines = [];

        foreach ($proxies as $proxy) {
            $line = self::proxyToQX($proxy);
            if ($line !== null) {
                $lines[] = $line;
            }
        }

        return implode("\n", $lines);
    }

    /**
     * 将单个代理转换为 QX 格式
     */
    private static function proxyToQX(array $proxy): ?string
    {
        switch ($proxy['type']) {
            case 'ss':
                return "shadowsocks={$proxy['server']}:{$proxy['port']}, method={$proxy['cipher']}, password={$proxy['password']}, tag={$proxy['name']}";
            
            case 'vmess':
                $line = "vmess={$proxy['server']}:{$proxy['port']}, method=chacha20-ietf-poly1305, password={$proxy['uuid']}, tag={$proxy['name']}";
                if ($proxy['tls'] ?? false) {
                    $line .= ", tls=1";
                }
                return $line;
            
            case 'trojan':
                $line = "trojan={$proxy['server']}:{$proxy['port']}, password={$proxy['password']}, tag={$proxy['name']}";
                if (isset($proxy['sni']) && !empty($proxy['sni'])) {
                    $line .= ", tls-host={$proxy['sni']}";
                }
                return $line;
            
            default:
                return null;
        }
    }

    /**
     * 生成 V2Ray URI 格式
     */
    private static function produceV2Ray(array $proxies): string
    {
        $uris = [];

        foreach ($proxies as $proxy) {
            $uri = self::proxyToV2RayURI($proxy);
            if ($uri !== null) {
                $uris[] = $uri;
            }
        }

        return base64_encode(implode("\n", $uris));
    }

    /**
     * 将单个代理转换为 V2Ray URI
     */
    private static function proxyToV2RayURI(array $proxy): ?string
    {
        switch ($proxy['type']) {
            case 'vmess':
                $vmessJson = json_encode([
                    'v' => '2',
                    'ps' => $proxy['name'],
                    'add' => $proxy['server'],
                    'port' => $proxy['port'],
                    'id' => $proxy['uuid'],
                    'aid' => $proxy['alterId'] ?? 0,
                    'net' => $proxy['network'] ?? 'tcp',
                    'type' => 'none',
                    'host' => '',
                    'path' => '',
                    'tls' => $proxy['tls'] ? 'tls' : '',
                ]);
                return 'vmess://' . base64_encode($vmessJson);
            
            case 'vless':
                $params = [
                    'type' => $proxy['network'] ?? 'tcp',
                ];
                if ($proxy['tls'] ?? false) {
                    $params['security'] = 'tls';
                }
                if (isset($proxy['sni']) && !empty($proxy['sni'])) {
                    $params['sni'] = $proxy['sni'];
                }
                
                $query = http_build_query($params);
                $name = urlencode($proxy['name']);
                return "vless://{$proxy['uuid']}@{$proxy['server']}:{$proxy['port']}?{$query}#{$name}";
            
            case 'trojan':
                $name = urlencode($proxy['name']);
                return "trojan://{$proxy['password']}@{$proxy['server']}:{$proxy['port']}#{$name}";
            
            case 'ss':
                $auth = base64_encode("{$proxy['cipher']}:{$proxy['password']}");
                $name = urlencode($proxy['name']);
                return "ss://{$auth}@{$proxy['server']}:{$proxy['port']}#{$name}";
            
            default:
                return null;
        }
    }

    /**
     * 生成 Sing-Box 格式
     */
    private static function produceSingBox(array $proxies): string
    {
        $outbounds = [];

        foreach ($proxies as $proxy) {
            $outbound = self::proxyToSingBox($proxy);
            if ($outbound !== null) {
                $outbounds[] = $outbound;
            }
        }

        return json_encode([
            'outbounds' => $outbounds,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * 将单个代理转换为 Sing-Box 格式
     */
    private static function proxyToSingBox(array $proxy): ?array
    {
        $outbound = [
            'type' => $proxy['type'],
            'tag' => $proxy['name'],
            'server' => $proxy['server'],
            'server_port' => $proxy['port'],
        ];

        switch ($proxy['type']) {
            case 'ss':
                $outbound['method'] = $proxy['cipher'];
                $outbound['password'] = $proxy['password'];
                break;

            case 'vmess':
                $outbound['uuid'] = $proxy['uuid'];
                $outbound['alter_id'] = $proxy['alterId'] ?? 0;
                if ($proxy['tls'] ?? false) {
                    $outbound['tls'] = ['enabled' => true];
                }
                break;

            case 'vless':
                $outbound['uuid'] = $proxy['uuid'];
                if ($proxy['tls'] ?? false) {
                    $outbound['tls'] = ['enabled' => true];
                }
                break;

            case 'trojan':
                $outbound['password'] = $proxy['password'];
                break;

            default:
                return null;
        }

        return $outbound;
    }

    /**
     * 生成 Stash 格式
     */
    private static function produceStash(array $proxies): string
    {
        // Stash 格式与 Clash 类似
        return self::produceClash($proxies);
    }
}
