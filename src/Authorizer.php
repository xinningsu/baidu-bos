<?php

namespace Sulao\BaiduBos;

class Authorizer
{
    const HEADERS_TO_SIGN = [
        'host',
        'content-length',
        'content-type',
        'content-md5',
    ];

    const BCE_HEADER_PREFIX = 'x-bce-';

    const EXPIRED_IN = 1800;

    protected $accessKey;

    protected $secretKey;

    public function __construct(string $accessKey, string $secretKey)
    {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
    }

    public function getAuthorization(
        string $method,
        string $path,
        array $query = [],
        array $headers = [],
        array $options = []
    ): string {
        $canonicalURI = $this->getCanonicalURI($path);
        $canonicalQueryString = $this->getCanonicalQueryString($query);

        $headerToSign = $this->getHeadersToSign(
            $headers,
            $options['sign_headers'] ?? [],
            $hasSignedHeader
        );
        $canonicalHeader = $this->getCanonicalHeaders($headerToSign);

        $canonicalRequest = implode(
            "\n",
            [$method, $canonicalURI, $canonicalQueryString, $canonicalHeader]
        );

        $signedHeaders = $hasSignedHeader
            ? $this->getSignedHeaders(array_keys($headerToSign))
            : '';

        $authPrefix = $this->authPrefix(
            $options['expired_in'] ?? self::EXPIRED_IN
        );
        $signingKey = hash_hmac('sha256', $authPrefix, $this->secretKey);
        $signature = hash_hmac('sha256', $canonicalRequest, $signingKey);

        return $authPrefix . '/' . $signedHeaders . '/' . $signature;
    }

    protected function authPrefix(int $expiredIn = self::EXPIRED_IN): string
    {
        return 'bce-auth-v1/' . $this->accessKey . '/'
            . gmdate('Y-m-d\TH:i:s\Z') . '/' . $expiredIn;
    }

    protected function getCanonicalURI(string $path): string
    {
        $path = '/' . ltrim($path, '/');

        return str_replace('%2F', '/', rawurlencode($path));
    }

    protected function getHeadersToSign(
        array $headers,
        array $signHeaders = [],
        &$hasSignedHeader = null
    ): array {
        $signHeaders = array_map('strtolower', $signHeaders);
        $len = strlen(self::BCE_HEADER_PREFIX);

        $hasSignedHeader = false;
        $arr = [];
        foreach ($headers as $key => $value) {
            if (
                in_array(strtolower($key), self::HEADERS_TO_SIGN)
                || substr($key, 0, $len) === self::BCE_HEADER_PREFIX
            ) {
                $arr[$key] = $value;
            } elseif (in_array(strtolower($key), $signHeaders)) {
                $arr[$key] = $value;
                $hasSignedHeader = true;
            }
        }

        return $arr;
    }

    protected function getCanonicalQueryString(array $query): string
    {
        $arr = array_map(function ($value, $key) {
            return rawurlencode($key) . '=' . rawurlencode($value);
        }, $query, array_keys($query));
        sort($arr);

        return implode('&', $arr);
    }


    protected function getCanonicalHeaders(array $headers): string
    {
        $arr = array_map(function ($value, $key) {
            return rawurlencode(strtolower(trim($key)))
                . ':' . rawurlencode(trim($value));
        }, $headers, array_keys($headers));
        sort($arr);

        return implode("\n", $arr);
    }

    protected function getSignedHeaders(array $headers): string
    {
        $headers = array_map(function ($header) {
            return rawurlencode(strtolower(trim($header)));
        }, $headers);
        sort($headers);

        return implode(';', $headers);
    }
}
