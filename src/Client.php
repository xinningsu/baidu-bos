<?php

namespace Sulao\BaiduBos;

use GuzzleHttp;
use GuzzleHttp\Exception\BadResponseException;
use Psr\Http\Message\ResponseInterface;

class Client
{
    const BOS_HOST = 'bcebos.com';

    protected $config;
    protected $authorizer;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->authorizer = new Authorizer($config);
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
    public function getObject(string $path, array $options = []): string
    {
        return $this->request('GET', $path, $options);
    }

    /**
     * Get the meta of an object
     *
     * @param string $path
     * @param array  $options
     *
     * @return array
     * @throws Exception
     */
    public function getObjectMeta(string $path, array $options = []): array
    {
        $options['return'] = 'headers';

        return $this->request('HEAD', $path, $options);
    }

    /**
     * Add an object
     *
     * @param string $path
     * @param string $content
     * @param array  $options
     *
     * @return array
     * @throws Exception
     */
    public function putObject(
        string $path,
        string $content,
        array $options = []
    ): array {
        $options['body'] = $content;
        $options['return'] = 'headers';

        return $this->request('PUT', $path, $options);
    }

    /**
     * Copy an object
     *
     * @param string $source
     * @param string $dest
     * @param array  $options
     *
     * @return array
     * @throws Exception
     */
    public function copyObject(
        string $source,
        string $dest,
        array $options = []
    ): array {
        $sourcePath = '/' . $this->config['bucket']
            . '/' . ltrim($source, '/');
        $options['headers']['x-bce-copy-source'] = $sourcePath;
        $options['return'] = 'json';

        return $this->request('PUT', $dest, $options);
    }

    /**
     * Fetch a remote source as an object
     *
     * @param string $path
     * @param string $source
     * @param array  $options
     *
     * @return array
     * @throws Exception
     */
    public function fetchObject(
        string $path,
        string $source,
        array $options = []
    ): array {
        $options['query']['fetch'] = null;
        $options['headers']['x-bce-fetch-source'] = $source;
        $options['return'] = 'json';

        return $this->request('POST', $path, $options);
    }

    /**
     *  Rename an object
     *
     * @param string $path
     * @param string $newPath
     * @param array  $options
     *
     * @return array
     * @throws Exception
     */
    public function renameObject(
        string $path,
        string $newPath,
        array $options = []
    ): array {
        $result = $this->copyObject($path, $newPath, $options);
        $this->deleteObject($path);

        return $result;
    }

    /**
     * Append content to an object
     *
     * @param string $path
     * @param string $content
     * @param array  $options
     *
     * @return array
     * @throws Exception
     */
    public function appendObject(
        string $path,
        string $content,
        array $options = []
    ): array {
        $options['query']['append'] = null;

        $options['body'] = $content;
        $options['return'] = 'headers';

        return $this->request('POST', $path, $options);
    }

    /**
     * Delete an object
     *
     * @param string $path
     * @param array  $options
     *
     * @return array
     * @throws Exception
     */
    public function deleteObject(string $path, array $options = []): array
    {
        $options['return'] = 'headers';

        return $this->request('DELETE', $path, $options);
    }


    /**
     * Delete objects
     *
     * @param array $paths
     * @param array  $options
     *
     * @return array
     * @throws Exception
     */
    public function deleteObjects(array $paths, array $options = []): array
    {
        $paths = array_map(function ($path) {
            return ['key' => ltrim($path, '/')];
        }, $paths);

        $options['query']['delete'] = null;
        $options['body'] = json_encode(['objects' => $paths]);
        $options['return'] = 'headers';

        return $this->request('POST', '/', $options);
    }

    /**
     * Get object ACL
     *
     * @param string $path
     * @param array  $options
     *
     * @return array
     * @throws Exception
     */
    public function getObjectAcl(string $path, array $options = []): array
    {
        $options['query'] = ['acl' => null];
        $options['return'] = 'json';

        return $this->request('GET', $path, $options);
    }

    /**
     * Update object ACL
     *
     * @param string $path
     * @param bool   $private
     * @param array  $options
     *
     * @return array
     * @throws Exception
     */
    public function putObjectAcl(
        string $path,
        bool $private,
        array $options = []
    ): array {
        $options['headers']['x-bce-acl'] = $private ? 'private' : 'public-read';
        $options['query']['acl'] = null;
        $options['return'] = 'headers';

        return $this->request('PUT', $path, $options);
    }

    /**
     * Delete object ACL
     *
     * @param string $path
     * @param array  $options
     *
     * @return array
     * @throws Exception
     */
    public function deleteObjectAcl(string $path, array $options = []): array
    {
        $options['query']['acl'] = null;
        $options['return'] = 'headers';

        return $this->request('DELETE', $path, $options);
    }

    /**
     * Get current bucket ACL
     *
     * @param array $options
     *
     * @return array
     * @throws Exception
     */
    public function getBucketAcl(array $options = []): array
    {
        $options['query']['acl'] = null;
        $options['return'] = 'json';

        return $this->request('GET', '/', $options);
    }

    public function listObjects(array $options = []): array
    {

        $options['return'] = 'json';

        return $this->request('GET', '/', $options);
    }

    /**
     * Request BOS api
     *
     * @param string $method
     * @param string $path
     * @param array  $options
     *
     * @return array|mixed|string
     * @throws Exception
     */
    protected function request(
        string $method,
        string $path,
        array $options = []
    ) {
        $query = $options['query'] ?? [];
        $headers = $options['headers'] ?? [];
        $body = $options['body'] ?? null;
        $path = '/' . ltrim($path, '/');

        $headers = $this->buildHeaders(
            $method,
            $path,
            $query,
            $headers,
            $body,
            $options['authorization'] ?? []
        );

        $endpoint = 'https://' . $headers['Host'] . $path;
        if (!empty($query)) {
            $endpoint .= '?' . $this->buildQuery($query);
        }

        $httpClient = new GuzzleHttp\Client();
        $guzzleOptions = ['headers' => $headers];

        if (!is_null($body)) {
            $guzzleOptions['body'] = $body;
        }

        try {
            $response = $httpClient->request(
                $method,
                $endpoint,
                $guzzleOptions
            );
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

        $return = $options['return'] ?? null;
        if ($return === 'headers') {
            return $this->parseHeaders($response);
        } elseif ($return === 'json') {
            return json_decode($response->getBody()->getContents(), true);
        }

        return $response->getBody()->getContents();
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
        string $method,
        string $path,
        array $query = [],
        array $headers = [],
        ?string $body = null,
        array $options = []
    ): array {
        $keys = array_map('strtolower', array_keys($headers));

        $headers['Host'] = $this->config['bucket'] . '.'
            . $this->config['region'] . '.' . self::BOS_HOST;

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
    protected function buildQuery(array $query): string
    {
        $arr = [];
        foreach ($query as $key => $value) {
            $arr[] = rawurlencode($key)
                . (isset($value) ? '=' . rawurlencode($value) : '');
        }

        return implode('&', $arr);
    }

    /**
     * Parse response headers
     *
     * @param ResponseInterface $response
     *
     * @return array
     */
    protected function parseHeaders(ResponseInterface $response): array
    {
        $headers = array();
        foreach ($response->getHeaders() as $name => $values) {
            $headers[$name] = count($values) == 1 ? reset($values) : $values;
        }

        return $headers;
    }
}
