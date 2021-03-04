<?php

namespace Sulao\BaiduBos;

/**
 * Class Client
 *
 * @package Sulao\BaiduBos
 */
class Client extends Request
{
    /**
     * @var array
     */
    protected $config;

    /**
     * Get config
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Get an object
     *
     * @param string $path
     * @param array  $options
     *
     * @return string|mixed
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
        $sourcePath = '/' . $this->config['bucket'] . '/' . ltrim($source, '/');
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
     * @return array|mixed
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
}
