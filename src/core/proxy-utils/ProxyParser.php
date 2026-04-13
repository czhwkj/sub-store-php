<?php

declare(strict_types=1);

namespace SubStore\Core\ProxyUtils;

use SubStore\Restful\errors as Errors;

/**
 * 代理解析器
 * 解析各种格式的代理订阅
 */
class ProxyParser
{
    /**
     * 解析原始订阅内容
     */
    public function parse(string $raw): array
    {
        $raw = trim($raw);

        // 判断格式类型
        if ($this->isBase64($raw)) {
            $raw = base64_decode($raw, true);
            if ($raw === false) {
                throw new Errors\RequestInvalidError(
                    'INVALID_FORMAT',
                    'Invalid base64 format'
                );
            }
        }

        // 按行分割
        $lines = preg_split('/\r\n|\r|\n/', $raw);
        $proxies = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || $this->isComment($line)) {
                continue;
            }

            $proxy = $this->parseLine($line);
            if ($proxy !== null) {
                $proxies[] = $proxy;
            }
        }

        if (empty($proxies)) {
            // 尝试作为 JSON 解析
            $jsonProxies = $this->parseJson($raw);
            if (!empty($jsonProxies)) {
                return $jsonProxies;
            }

            // 尝试作为 YAML 解析
            $yamlProxies = $this->parseYaml($raw);
            if (!empty($yamlProxies)) {
                return $yamlProxies;
            }
        }

        return $proxies;
    }

    /**
     * 解析单行
     */
    private function parseLine(string $line): ?array
    {
        // 按优先级尝试不同的解析器
        $parsers = [
            'vless',
            'vmess',
            'trojan',
            'ss',
            'socks',
            'http',
            'https',
        ];

        foreach ($parsers as $parserName) {
            $method = 'parse' . ucfirst($parserName);
            if (method_exists($this, $method)) {
                try {
                    $proxy = $this->$method($line);
                    if ($proxy !== null) {
                        return $proxy;
                    }
                } catch (\Exception $e) {
                    // 继续尝试下一个解析器
                }
            }
        }

        return null;
    }

    /**
     * 解析 VLESS 链接
     */
    private function parseVless(string $line): ?array
    {
        if (!preg_match('/^vless:\/\/([^@]+)@([^:]+):(\d+)/', $line, $matches)) {
            return null;
        }

        $uuid = $matches[1];
        $server = $matches[2];
        $port = (int)$matches[3];

        // 解析查询参数
        $urlParts = parse_url($line);
        $queryParams = [];
        if (isset($urlParts['query'])) {
            parse_str($urlParts['query'], $queryParams);
        }

        $name = isset($urlParts['fragment']) ? urldecode($urlParts['fragment']) : "VLESS {$server}:{$port}";

        return [
            'name' => $name,
            'type' => 'vless',
            'server' => $server,
            'port' => $port,
            'uuid' => $uuid,
            'network' => $queryParams['type'] ?? 'tcp',
            'tls' => in_array($queryParams['security'] ?? '', ['tls', 'xtls']),
            'sni' => $queryParams['sni'] ?? '',
            'path' => $queryParams['path'] ?? '',
            'host' => $queryParams['host'] ?? '',
        ];
    }

    /**
     * 解析 VMESS 链接
     */
    private function parseVmess(string $line): ?array
    {
        if (!preg_match('/^vmess:\/\//', $line)) {
            return null;
        }

        $jsonPart = substr($line, 8);
        $decoded = base64_decode($jsonPart, true);

        if ($decoded === false) {
            return null;
        }

        $data = json_decode($decoded, true);
        if (!$data || !is_array($data)) {
            return null;
        }

        return [
            'name' => $data['ps'] ?? $data['name'] ?? "VMESS",
            'type' => 'vmess',
            'server' => $data['add'] ?? $data['server'] ?? '',
            'port' => (int)($data['port'] ?? 443),
            'uuid' => $data['id'] ?? $data['uuid'] ?? '',
            'alterId' => (int)($data['aid'] ?? $data['alterId'] ?? 0),
            'network' => $data['net'] ?? $data['network'] ?? 'tcp',
            'tls' => isset($data['tls']) && $data['tls'] === 'tls',
            'sni' => $data['sni'] ?? $data['host'] ?? '',
            'path' => $data['path'] ?? '',
            'host' => $data['host'] ?? '',
        ];
    }

    /**
     * 解析 Trojan 链接
     */
    private function parseTrojan(string $line): ?array
    {
        if (!preg_match('/^trojan:\/\//', $line)) {
            return null;
        }

        $urlParts = parse_url($line);
        if (!$urlParts || !isset($urlParts['host']) || !isset($urlParts['port'])) {
            return null;
        }

        $password = $urlParts['user'] ?? '';
        $server = $urlParts['host'];
        $port = (int)$urlParts['port'];
        $name = isset($urlParts['fragment']) ? urldecode($urlParts['fragment']) : "Trojan {$server}:{$port}";

        $queryParams = [];
        if (isset($urlParts['query'])) {
            parse_str($urlParts['query'], $queryParams);
        }

        return [
            'name' => $name,
            'type' => 'trojan',
            'server' => $server,
            'port' => $port,
            'password' => $password,
            'sni' => $queryParams['sni'] ?? '',
            'network' => $queryParams['type'] ?? 'tcp',
            'path' => $queryParams['path'] ?? '',
            'host' => $queryParams['host'] ?? '',
        ];
    }

    /**
     * 解析 Shadowsocks 链接
     */
    private function parseSs(string $line): ?array
    {
        if (!preg_match('/^ss:\/\//', $line)) {
            return null;
        }

        // 移除 ss://
        $withoutProtocol = substr($line, 5);

        // 尝试查找 @ 来分割认证信息和服务器信息
        $atPos = strpos($withoutProtocol, '@');
        if ($atPos === false) {
            // 旧格式: ss://method:password@server:port#name
            // 需要先解码 base64
            return null;
        }

        $authPart = substr($withoutProtocol, 0, $atPos);
        $serverPart = substr($withoutProtocol, $atPos + 1);

        // 解码认证部分
        $authDecoded = base64_decode($authPart, true);
        if ($authDecoded === false) {
            return null;
        }

        $authParts = explode(':', $authDecoded, 2);
        if (count($authParts) < 2) {
            return null;
        }

        $cipher = $authParts[0];
        $password = $authParts[1];

        // 解析服务器部分
        $serverUrl = parse_url('http://' . $serverPart);
        if (!$serverUrl || !isset($serverUrl['host']) || !isset($serverUrl['port'])) {
            return null;
        }

        $server = $serverUrl['host'];
        $port = (int)$serverUrl['port'];
        $name = isset($serverUrl['fragment']) ? urldecode($serverUrl['fragment']) : "SS {$server}:{$port}";

        return [
            'name' => $name,
            'type' => 'ss',
            'server' => $server,
            'port' => $port,
            'cipher' => $cipher,
            'password' => $password,
        ];
    }

    /**
     * 解析 SOCKS 链接
     */
    private function parseSocks(string $line): ?array
    {
        if (!preg_match('/^socks5?:\/\//', $line)) {
            return null;
        }

        $urlParts = parse_url($line);
        if (!$urlParts || !isset($urlParts['host'])) {
            return null;
        }

        $server = $urlParts['host'];
        $port = (int)($urlParts['port'] ?? 1080);
        $name = isset($urlParts['fragment']) ? urldecode($urlParts['fragment']) : "SOCKS {$server}:{$port}";

        return [
            'name' => $name,
            'type' => 'socks5',
            'server' => $server,
            'port' => $port,
            'username' => $urlParts['user'] ?? '',
            'password' => $urlParts['pass'] ?? '',
        ];
    }

    /**
     * 解析 HTTP/HTTPS 链接
     */
    private function parseHttp(string $line): ?array
    {
        if (!preg_match('/^https?:\/\//', $line)) {
            return null;
        }

        $urlParts = parse_url($line);
        if (!$urlParts || !isset($urlParts['host'])) {
            return null;
        }

        $server = $urlParts['host'];
        $port = (int)($urlParts['port'] ?? (strpos($line, 'https') === 0 ? 443 : 80));
        $name = isset($urlParts['fragment']) ? urldecode($urlParts['fragment']) : "HTTP {$server}:{$port}";

        return [
            'name' => $name,
            'type' => strpos($line, 'https') === 0 ? 'https' : 'http',
            'server' => $server,
            'port' => $port,
            'username' => $urlParts['user'] ?? '',
            'password' => $urlParts['pass'] ?? '',
            'tls' => strpos($line, 'https') === 0,
        ];
    }

    /**
     * 解析 JSON 格式
     */
    private function parseJson(string $raw): array
    {
        $data = json_decode($raw, true);
        if (!$data || !is_array($data)) {
            return [];
        }

        $proxies = [];

        // Clash 格式
        if (isset($data['proxies']) && is_array($data['proxies'])) {
            foreach ($data['proxies'] as $proxy) {
                if (is_array($proxy)) {
                    $proxies[] = ProxyUtils::standardizeProxy($proxy);
                }
            }
        }

        // 直接是代理数组
        if (empty($proxies) && isset($data[0]) && is_array($data[0])) {
            foreach ($data as $proxy) {
                if (is_array($proxy)) {
                    $proxies[] = ProxyUtils::standardizeProxy($proxy);
                }
            }
        }

        return $proxies;
    }

    /**
     * 解析 YAML 格式 (简化版)
     */
    private function parseYaml(string $raw): array
    {
        // 这里需要一个简单的 YAML 解析器
        // 为了简化，暂时返回空数组
        // TODO: 实现 YAML 解析
        return [];
    }

    /**
     * 判断是否为 Base64 编码
     */
    private function isBase64(string $str): bool
    {
        // 检查是否只包含 Base64 字符
        return (bool) preg_match('/^[A-Za-z0-9+\/]+=*$/', $str);
    }

    /**
     * 判断是否为注释
     */
    private function isComment(string $line): bool
    {
        return strpos($line, '#') === 0 || strpos($line, '//') === 0;
    }
}
