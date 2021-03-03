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
class Client
{
    const BOS_HOST = 'bcebos.com';

    /**
     * @var string
     */
    protected $accessKey;

    /**
     * @var string
     */
    protected $secretKey;

    /**
     * @var string
     */
    protected $bucket;

    /**
     * @var string
     */
    protected $region;

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
     * @param string $accessKey
     * @param string $secretKey
     * @param string $bucket
     * @param string $region
     * @param array  $options    Guzzle request options
     */
    public function __construct(
        $accessKey,
        $secretKey,
        $bucket,
        $region,
        array $options = []
    ) {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->bucket = $bucket;
        $this->region = $region;
        $this->options = $options + $this->options;

        $this->authorizer = new Authorizer($this->accessKey, $this->secretKey);
    }

    /**
     * Get access key
     *
     * @return string
     */
    public function getAccessKey()
    {
        return $this->accessKey;
    }

    /**
     * Get secret key
     *
     * @return string
     */
    public function getSecretKey()
    {
        return $this->secretKey;
    }

    /**
     * Get bucket
     *
     * @return string
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * Get region
     *
     * @return string
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * Get an object
     *
     * @param string $path
     * @param array  $options
     *
     * @return string
     * @throws Exception
     */
    public function getObject($path, array $options = [])
    {
        return $this->request('GET', $path, $options);
    }

    /**
     * Get the meta of an object
     *
     * @param string $path
     * @param array  $options
     *
     * @return array|mixed
     * @throws Exception
     */
    public function getObjectMeta($path, array $options = [])
    {
        $options += ['return' => 'headers'];

        return $this->request('HEAD', $path, $options);
    }

    /**
     * Add an object
     *
     * @param string $path
     * @param string $content
     * @param array  $options
     *
     * @return array|mixed
     * @throws Exception
     */
    public function putObject($path, $content, array $options = [])
    {
        $options['body'] = $content;
        $options += ['return' => 'headers'];

        return $this->request('PUT', $path, $options);
    }

    /**
     * Copy an object
     *
     * @param string $source
     * @param string $dest
     * @param array  $options
     *
     * @return array|mixed
     * @throws Exception
     */
    public function copyObject($source, $dest, array $options = [])
    {
        $sourcePath = '/' . $this->bucket . '/' . ltrim($source, '/');
        $options['headers']['x-bce-copy-source'] = $sourcePath;
        $options += ['return' => 'body-json'];

        return $this->request('PUT', $dest, $options);
    }

    /**
     * Fetch a remote source as an object
     *
     * @param string $path
     * @param string $source
     * @param array  $options
     *
     * @return array|mixed
     * @throws Exception
     */
    public function fetchObject($path, $source, array $options = [])
    {
        $options['query']['fetch'] = null;
        $options['headers']['x-bce-fetch-source'] = $source;
        $options += ['return' => 'body-json'];

        return $this->request('POST', $path, $options);
    }

    /**
     * Append content to an object
     *
     * @param string $path
     * @param string $content
     * @param array  $options
     *
     * @return array|mixed
     * @throws Exception
     */
    public function appendObject($path, $content, array $options = [])
    {
        $options['query']['append'] = null;

        $options['body'] = $content;
        $options += ['return' => 'headers'];

        return $this->request('POST', $path, $options);
    }

    /**
     * Delete an object
     *
     * @param string $path
     * @param array  $options
     *
     * @return array|mixed
     * @throws Exception
     */
    public function deleteObject($path, array $options = [])
    {
        $options += ['return' => 'headers'];

        return $this->request('DELETE', $path, $options);
    }


    /**
     * Delete objects
     *
     * @param array $paths
     * @param array  $options
     *
     * @return array|mixed
     * @throws Exception
     */
    public function deleteObjects(array $paths, array $options = [])
    {
        $paths = array_map(function ($path) {
            return ['key' => ltrim($path, '/')];
        }, $paths);

        $options['query']['delete'] = null;
        $options['body'] = json_encode(['objects' => $paths]);
        $options += ['return' => 'headers'];

        return $this->request('POST', '/', $options);
    }

    /**
     * Get object ACL
     *
     * @param string $path
     * @param array  $options
     *
     * @return array|mixed
     * @throws Exception
     */
    public function getObjectAcl($path, array $options = [])
    {
        $options['query'] = ['acl' => null];
        $options += ['return' => 'body-json'];

        return $this->request('GET', $path, $options);
    }

    /**
     * Update object ACL
     *
     * @param string $path
     * @param string $acl     private or public-read
     * @param array  $options
     *
     * @return array
     * @throws Exception
     */
    public function putObjectAcl($path, $acl, array $options = [])
    {
        if (!in_array($acl, ['private', 'public-read'])) {
            throw new Exception(
                'Unsupported acl: ' . $acl . ', either private or public-read'
            );
        }

        $options['headers']['x-bce-acl'] = $acl;
        $options['query']['acl'] = null;
        $options += ['return' => 'headers'];

        return $this->request('PUT', $path, $options);
    }

    /**
     * Delete object ACL
     *
     * @param string $path
     * @param array  $options
     *
     * @return array|mixed
     * @throws Exception
     */
    public function deleteObjectAcl($path, array $options = [])
    {
        $options['query']['acl'] = null;
        $options += ['return' => 'headers'];

        return $this->request('DELETE', $path, $options);
    }

    /**
     * Get current bucket ACL
     *
     * @param array $options
     *
     * @return array|mixed
     * @throws Exception
     */
    public function getBucketAcl(array $options = [])
    {
        $options['query']['acl'] = null;
        $options += ['return' => 'body-json'];

        return $this->request('GET', '/', $options);
    }

    /**
     * List objects
     *
     * @param array $options
     *
     * @return array|mixed|string
     * @throws Exception
     */
    public function listObjects(array $options = [])
    {
        $options += ['return' => 'body-json'];

        return $this->request('GET', '/', $options);
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

        return $this->processReturn(
            $response,
            isset($options['return']) ? $options['return'] : null
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

        $headers['Host'] = $this->bucket . '.' . $this->region
            . '.' . self::BOS_HOST;

        if (!array_key_exists('date', $keys)) {
            $headers['Date'] = gmdate('D, d M Y H:i:s e');
        }

        if (!array_key_exists('content-length', $keys)) {
            $headers['Content-Length'] = isset($body) ? strlen($body) : 0;
        }

        if (!array_key_exists('content-md5', $keys) && strlen($body)) {
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
            return json_decode($response->getBody()->getContents(), true);
        } elseif ($format === 'headers') {
            return $this->parseHeaders($response->getHeaders());
        } elseif ($format === 'both') {
            return [
                'headers' => $this->parseHeaders($response->getHeaders()),
                'body' => $response->getBody()->getContents(),
            ];
        } else {
            return $response->getBody()->getContents();
        }
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
