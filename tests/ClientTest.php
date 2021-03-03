<?php

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

class ClientTest extends \PHPUnit\Framework\TestCase
{
    protected function client()
    {
        static $client;

        if (!$client) {
            $client = new \Sulao\BaiduBos\Client(
                getenv('BOS_KEY'),
                getenv('BOS_SECRET'),
                'xinningsu',
                'gz',
                ['connect_timeout' => 10]
            );
        }

        return $client;
    }

    public function testClient()
    {
        $this->assertEquals(getenv('BOS_KEY'), $this->client()->getAccessKey());
        $this->assertEquals(
            getenv('BOS_SECRET'),
            $this->client()->getSecretKey()
        );
        $this->assertEquals('xinningsu', $this->client()->getBucket());
        $this->assertEquals('gz', $this->client()->getRegion());
    }

    public function testBucket()
    {
        $acl = $this->client()->getBucketAcl();
        $this->assertEquals(
            'READ',
            $acl['accessControlList'][0]['permission'][0]
        );
    }

    public function testObject()
    {
        $this->putObject();
        $this->getObject();
        $this->copyObject();
        $this->getObjectMeta();
        $this->objectAcl();
        $this->appendObject();
        $this->fetchObject();
        $this->deleteObject();
        $this->listObjects();
        $this->deleteObjects();
    }

    protected function putObject()
    {
        $exception = null;
        try {
            $this->client()->putObject('/bos_test.txt', 'bos test');
            $this->client()->putObject('/bos_test/bos_test10.txt', 'bos test');
        } catch (Exception $exception) {
        }

        $this->assertNull($exception);
    }

    protected function getObject()
    {
        $part = $this->client()->getObject(
            '/bos_test.txt',
            ['headers' => ['Range' => 'bytes=0-2']]
        );
        $this->assertEquals('bos', $part);

        $content = $this->client()->getObject(
            '/bos_test.txt',
            [
                'headers' => ['Author' => 'Thomas'],
                'authorize' => [
                    'sign_headers' => ['Author'],
                    'expired_in' => 1800
                ],
                'request' => ['connect_timeout' => 15]
            ]
        );
        $this->assertEquals('bos test', $content);
    }

    protected function copyObject()
    {
        $content = $this->client()->getObject('/bos_test.txt');
        $this->client()->copyObject('/bos_test.txt', '/bos_test2.txt');
        $this->assertEquals(
            $content,
            $this->client()->getObject('/bos_test2.txt')
        );
    }

    protected function getObjectMeta()
    {
        $meta = $this->client()->getObjectMeta('/bos_test.txt');
        $this->assertTrue(is_array($meta));
        $this->assertArrayHasKey('Content-Length', $meta);
        $this->assertEquals(8, $meta['Content-Length']);
    }

    protected function objectAcl()
    {
        $this->client()->putObjectAcl('/bos_test.txt', 'public-read');
        $acl = $this->client()->getObjectAcl('/bos_test.txt');
        $this->assertEquals(
            'READ',
            $acl['accessControlList'][0]['permission'][0]
        );

        $this->client()->deleteObjectAcl('/bos_test.txt');
        $exception  = null;
        try {
            $this->client()->getObjectAcl('/bos_test.txt');
        } catch (\Sulao\BaiduBos\Exception $exception) {
        }
        $this->assertNotNull($exception);
        $this->assertEquals('ObjectAclNotExists', $exception->bosCode);

        $exception  = null;
        try {
            $this->client()->putObjectAcl('/bos_test.txt', 'aaaaaa');
        } catch (\Sulao\BaiduBos\Exception $exception) {
        }
        $this->assertNotNull($exception);
    }

    protected function appendObject()
    {
        $this->client()->appendObject('/bos_test.txt', 'bos test');
        $this->assertEquals(
            'bos test',
            $this->client()->getObject('/bos_test.txt')
        );
        $this->client()->appendObject(
            '/bos_test.txt',
            '.',
            ['query' => ['offset' => 8]]
        );
        $this->assertEquals(
            'bos test.',
            $this->client()->getObject('/bos_test.txt')
        );
    }

    protected function fetchObject()
    {
        $this->client()->fetchObject('/bos_test3.txt', 'https://www.baidu.com');
        $result = $this->client()->getObjectMeta(
            '/bos_test3.txt',
            ['return' => 'both']
        );
        $this->assertTrue(is_array($result));
        $this->assertArrayHasKey('headers', $result);
        $this->assertArrayHasKey('body', $result);
    }

    protected function listObjects()
    {
        $result = $this->client()->listObjects();
        $lists = array_column($result['contents'], 'key');
        $this->assertTrue(in_array('bos_test.txt', $lists));
        $this->assertTrue(in_array('bos_test/bos_test10.txt', $lists));

        $result = $this->client()->listObjects(['query' => ['delimiter' => '/']]);
        $lists = array_column($result['contents'], 'key');
        $this->assertTrue(in_array('bos_test.txt', $lists));
        $this->assertFalse(in_array('bos_test/bos_test10.txt', $lists));

        $result = $this->client()->listObjects([
            'query' => ['prefix' => 'bos_test/']
        ]);
        $lists = array_column($result['contents'], 'key');
        $this->assertFalse(in_array('bos_test.txt', $lists));
        $this->assertTrue(in_array('bos_test/bos_test10.txt', $lists));
    }

    protected function deleteObject()
    {
        $this->client()->deleteObject('/bos_test3.txt');
        $exception  = null;
        try {
            $this->client()->getObject('/bos_test3.txt');
        } catch (\Sulao\BaiduBos\Exception $exception) {
        }
        $this->assertNotNull($exception);
        $this->assertEquals(404, $exception->getCode());
    }

    protected function deleteObjects()
    {
        $this->client()->deleteObjects([
            'bos_test.txt',
            'bos_test2.txt',
            'bos_test/bos_test10.txt'
        ]);

        $exception  = null;
        try {
            $this->client()->getObjectMeta('/bos_test.txt');
        } catch (\Sulao\BaiduBos\Exception $exception) {
        }
        $this->assertNotNull($exception);
        $this->assertEquals(404, $exception->getCode());

        $exception  = null;
        try {
            $this->client()->getObjectMeta('/bos_test2.txt');
        } catch (\Sulao\BaiduBos\Exception $exception) {
        }
        $this->assertNotNull($exception);
        $this->assertEquals(404, $exception->getCode());

        $exception  = null;
        try {
            $this->client()->getObjectMeta('/bos_test/bos_test10.txt');
        } catch (\Sulao\BaiduBos\Exception $exception) {
        }
        $this->assertNotNull($exception);
        $this->assertEquals(404, $exception->getCode());
    }
}
