<?php

declare(strict_types=1);

namespace SubStore\Utils;

use SubStore\Restful\errors as Errors;

/**
 * 下载工具类
 * 用于下载远程订阅内容
 */
class Download
{
    /**
     * 下载远程内容
     */
    public static function download(string $url, array $options = []): string
    {
        $ch = curl_init();

        $timeout = $options['timeout'] ?? 30;
        $userAgent = $options['userAgent'] ?? 'Sub-Store/1.0.0';
        $headers = $options['headers'] ?? [];

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_USERAGENT => $userAgent,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            throw new Errors\NetworkError(
                'DOWNLOAD_FAILED',
                "Failed to download: {$url}",
                $error
            );
        }

        if ($httpCode >= 400) {
            throw new Errors\NetworkError(
                'HTTP_ERROR',
                "HTTP {$httpCode} for: {$url}"
            );
        }

        return $response;
    }

    /**
     * 获取订阅内容（自动处理远程和本地）
     */
    public static function fetchSubscription(array $subscription): string
    {
        if ($subscription['source'] === 'local') {
            return $subscription['content'] ?? '';
        }

        if ($subscription['source'] === 'remote') {
            $url = $subscription['url'] ?? '';
            if (empty($url)) {
                throw new Errors\RequestInvalidError(
                    'NO_URL',
                    'Remote subscription has no URL'
                );
            }

            $options = [];
            if (isset($subscription['userAgent'])) {
                $options['userAgent'] = $subscription['userAgent'];
            }

            return self::download($url, $options);
        }

        throw new Errors\RequestInvalidError(
            'INVALID_SOURCE',
            "Unknown source type: {$subscription['source']}"
        );
    }

    /**
     * 下载并解析订阅
     */
    public static function fetchAndParse(array $subscription, callable $parser): array
    {
        $content = self::fetchSubscription($subscription);
        return $parser($content);
    }
}
