<?php

namespace Sulao\BaiduBos;

use GuzzleHttp;
use GuzzleHttp\Exception\BadResponseException;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Client
 *
 * @package Sulao\BaiduBos
 */
abstract class Request
{
    const BOS_HOST = 'bcebos.com';

    /**
     * @var array
     */
    protected $config;

    /**
     * @var array Guzzle request options
     */
    protected $options = ['connect_timeout' => 10];

    /**
     * @var Authorizer
     */
    protected $authorizer;

    /**
     * Client constructor.
     *
     * @param array $config
     *
     * @throws Exception
     */
    public function __construct(array $config)
    {
        $keys = array_diff(
            ['access_key', 'secret_key', 'bucket', 'region'],
            array_keys($config)
        );
        if (!empty($keys)) {
            throw new Exception(
                'Invalid config, missing: ' . implode(',', $keys)
            );
        }

        if (isset($config['options']) && is_array($config['options'])) {
            $this->options = $config['options'] + $this->options;
        }

        $this->authorizer = new Authorizer(
            $config['access_key'],
            $config['secret_key']
        );

        $this->config = $config;
    }

    /**
     * Request BOS api
     *
     * @param string $method
     * @param string $path
     * @param array  $options
     *
     * @return array|string|mixed
     * @throws Exception
     */
    protected function request($method, $path, array $options = [])
    {
        list($endpoint, $requestOptions) = $this
            ->buildRequestOptions($method, $path, $options);

        $httpClient = new GuzzleHttp\Client($this->options);

        try {
            $response = $httpClient
                ->request($method, $endpoint, $requestOptions);
        } catch (\Exception $exception) {
            $this->handleRquestException($exception);
        }

        return $this->processReturn(
            $response,
            isset($options['return']) ? $options['return'] : null
        );
    }

    /**
     * Handle http request exception
     *
     * @param \Exception $exception
     *
     * @throws Exception
     */
    protected function handleRquestException(\Exception $exception)
    {
        if ($exception instanceof BadResponseException) {
            $content = $exception->getResponse()->getBody()->getContents();
            $response = json_decode($content, true);
            if (isset($response['code']) && isset($response['message'])) {
                $newException = new Exception(
                    $response['message'],
                    $exception->getCode(),
                    $exception
                );

                $newException->bosCode = $response['code'];

                if (isset($response['requestId'])) {
                    $newException->requestId = $response['requestId'];
                }

                throw $newException;
            }
        }

        throw new Exception(
            $exception->getMessage(),
            $exception->getCode(),
            $exception
        );
    }

    /**
     * Build endpoint and request options for guzzle to request
     *
     * @param string $method
     * @param string $path
     * @param array  $options
     *
     * @return array
     */
    protected function buildRequestOptions($method, $path, array $options = [])
    {
        $path = '/' . ltrim($path, '/');

        $query = isset($options['query']) ? $options['query'] : [];
        $headers = isset($options['headers']) ? $options['headers'] : [];
        $body = isset($options['body']) ? $options['body'] : null;
        $requestOptions = isset($options['request']) ? $options['request'] : [];

        $headers = $this->buildHeaders(
            $method,
            $path,
            $query,
            $headers,
            $body,
            isset($options['authorize']) ? $options['authorize'] : []
        );
        $requestOptions['headers'] = $headers;

        if (!is_null($body)) {
            $requestOptions['body'] = $body;
        }

        $endpoint = 'https://' . $headers['Host'] . $path;
        if (!empty($query)) {
            $endpoint .= '?' . $this->buildQuery($query);
        }

        return [$endpoint, $requestOptions];
    }

    /**
     * Build BOS api request header
     *
     * @param string      $method
     * @param string      $path
     * @param array       $query
     * @param array       $headers
     * @param string|null $body
     * @param array       $options
     *
     * @return array
     */
    protected function buildHeaders(
        $method,
        $path,
        array $query = [],
        array $headers = [],
        $body = null,
        array $options = []
    ) {
        $keys = array_map('strtolower', array_keys($headers));

        $headers['Host'] = $this->config['bucket'] . '.'
            . $this->config['region'] . '.' . self::BOS_HOST;

        if (!array_key_exists('date', $keys)) {
            $headers['Date'] = gmdate('D, d M Y H:i:s e');
        }

        if (!array_key_exists('content-length', $keys)) {
            $headers['Content-Length'] = isset($body) ? strlen($body) : 0;
        }

        if (
            !array_key_exists('content-md5', $keys)
            && isset($body) && strlen($body)
        ) {
            $headers['Content-MD5'] = base64_encode(md5($body, true));
        }

        if (!array_key_exists('authorization', $keys)) {
            $headers['Authorization'] = $this->authorizer
                ->getAuthorization($method, $path, $query, $headers, $options);
        }

        return $headers;
    }

    /**
     *  Build query string
     *
     * @param array $query
     *
     * @return string
     */
    protected function buildQuery(array $query)
    {
        $arr = [];
        foreach ($query as $key => $value) {
            $arr[] = rawurlencode($key)
                . (isset($value) ? '=' . rawurlencode($value) : '');
        }

        return implode('&', $arr);
    }

    /**
     * Process the return base on format
     *
     * @param ResponseInterface $response
     * @param string|null       $format
     *
     * @return array|string|mixed
     */
    protected function processReturn(
        ResponseInterface $response,
        $format = null
    ) {
        if ($format === 'body-json') {
            $return = json_decode($response->getBody()->getContents(), true);
        } elseif ($format === 'headers') {
            $return = $this->parseHeaders($response->getHeaders());
        } elseif ($format === 'both') {
            $return = [
                'headers' => $this->parseHeaders($response->getHeaders()),
                'body' => $response->getBody()->getContents(),
            ];
        } else {
            $return = $response->getBody()->getContents();
        }

        return $return;
    }

    /**
     * Parse response headers
     *
     * @param array $responseHeaders
     *
     * @return array
     */
    protected function parseHeaders(array $responseHeaders)
    {
        $headers = array();
        foreach ($responseHeaders as $name => $values) {
            $headers[$name] = count($values) == 1 ? reset($values) : $values;
        }

        return $headers;
    }
}
