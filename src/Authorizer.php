<?php

namespace Sulao\BaiduBos;

/**
 * Class Authorizer
 *
 * @package Sulao\BaiduBos
 */
class Authorizer
{
    const HEADERS_TO_SIGN = [
        'host',
        'content-length',
        'content-type',
        'content-md5',
    ];

    const BCE_HEADER_PREFIX = 'x-bce-';

    /**
     * @var int
     */
    protected $expiredIn = 1800;

    /**
     * @var string
     */
    protected $accessKey;

    /**
     * @var string
     */
    protected $secretKey;

    /**
     * Authorizer constructor.
     *
     * @param string $accessKey
     * @param string $secretKey
     */
    public function __construct($accessKey, $secretKey)
    {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
    }

    /**
     * @param string $method
     * @param string $path
     * @param array  $query
     * @param array  $headers
     * @param array  $options
     *
     * @return string
     */
    public function getAuthorization(
        $method,
        $path,
        array $query = [],
        array $headers = [],
        array $options = []
    ) {
        $canonicalURI = $this->getCanonicalURI($path);
        $canonicalQueryString = $this->getCanonicalQueryString($query);

        $headerToSign = $this->getHeadersToSign(
            $headers,
            isset($options['sign_headers']) ? $options['sign_headers'] : [],
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

        $expiredIn = isset($options['expired_in'])
            ? $options['expired_in']
            : $this->expiredIn;
        $authPrefix = $this->authPrefix($expiredIn);
        $signingKey = hash_hmac('sha256', $authPrefix, $this->secretKey);
        $signature = hash_hmac('sha256', $canonicalRequest, $signingKey);

        return $authPrefix . '/' . $signedHeaders . '/' . $signature;
    }

    /**
     * @param int $expiredIn
     *
     * @return string
     */
    protected function authPrefix($expiredIn)
    {
        return 'bce-auth-v1/' . $this->accessKey . '/'
            . gmdate('Y-m-d\TH:i:s\Z') . '/' . $expiredIn;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    protected function getCanonicalURI($path)
    {
        $path = '/' . ltrim($path, '/');

        return str_replace('%2F', '/', rawurlencode($path));
    }

    /**
     * @param array $headers
     * @param array $signHeaders
     * @param null  $hasSignedHeader
     *
     * @return array
     */
    protected function getHeadersToSign(
        array $headers,
        array $signHeaders = [],
        &$hasSignedHeader = null
    ) {
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

    /**
     * @param array $query
     *
     * @return string
     */
    protected function getCanonicalQueryString(array $query)
    {
        $arr = array_map(function ($value, $key) {
            return rawurlencode($key) . '=' . rawurlencode($value);
        }, $query, array_keys($query));
        sort($arr);

        return implode('&', $arr);
    }

    /**
     * @param array $headers
     *
     * @return string
     */
    protected function getCanonicalHeaders(array $headers)
    {
        $arr = array_map(function ($value, $key) {
            return rawurlencode(strtolower(trim($key)))
                . ':' . rawurlencode(trim($value));
        }, $headers, array_keys($headers));
        sort($arr);

        return implode("\n", $arr);
    }

    /**
     * @param array $headers
     *
     * @return string
     */
    protected function getSignedHeaders(array $headers)
    {
        $headers = array_map(function ($header) {
            return rawurlencode(strtolower(trim($header)));
        }, $headers);
        sort($headers);

        return implode(';', $headers);
    }
}
