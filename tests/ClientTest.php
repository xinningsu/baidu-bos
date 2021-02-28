<?php

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

class ClientTest extends \PHPUnit\Framework\TestCase
{
    protected $client;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = new \Sulao\BaiduBos\Client(
            getenv('BOS_KEY'),
            getenv('BOS_SECRET'),
            'xinningsu',
            'gz'
        );
    }

    public function testClient()
    {
        $this->assertEquals(getenv('BOS_KEY'), $this->client->getAccessKey());
        $this->assertEquals(
            getenv('BOS_SECRET'),
            $this->client->getSecretKey()
        );
        $this->assertEquals('xinningsu', $this->client->getBucket());
        $this->assertEquals('gz', $this->client->getRegion());
    }

    public function testBucket()
    {
        $acl = $this->client->getBucketAcl();
        $this->assertEquals(
            'READ',
            $acl['accessControlList'][0]['permission'][0]
        );
    }

    public function testObject()
    {
        $this->testPutObject();
        $this->testGetObject();
        $this->testCopyObject();
        $this->testRenameObject();
        $this->testDeleteObject();
        $this->testGetObjectMeta();
        $this->testObjectAcl();
        $this->testAppendObject();
        $this->testFetchObject();
        $this->testListObjects();
        $this->testDeleteObjects();
    }

    protected function testPutObject()
    {
        $exception = null;
        try {
            $this->client->putObject('/bos_test.txt', 'bos test');
            $this->client->putObject('/bos_test/bos_test10.txt', 'bos test');
        } catch (Exception $exception) {
        }

        $this->assertNull($exception);
    }

    protected function testGetObject()
    {
        $part = $this->client->getObject(
            '/bos_test.txt',
            ['headers' => ['Range' => 'bytes=0-2']]
        );
        $this->assertEquals('bos', $part);

        $content = $this->client->getObject(
            '/bos_test.txt',
            [
                'headers' => ['Author' => 'Thomas'],
                'authorization' => ['sign_headers' => ['Author']],
            ]
        );
        $this->assertEquals('bos test', $content);
    }

    protected function testCopyObject()
    {
        $content = $this->client->getObject('/bos_test.txt');
        $this->client->copyObject('/bos_test.txt', '/bos_test2.txt');
        $this->assertEquals(
            $content,
            $this->client->getObject('/bos_test2.txt')
        );
    }

    protected function testRenameObject()
    {
        $content = $this->client->getObject('/bos_test2.txt');

        $this->client->renameObject('/bos_test2.txt', '/bos_test3.txt');
        $this->assertEquals(
            $content,
            $this->client->getObject('/bos_test3.txt')
        );

        $exception  = null;
        try {
            $this->client->getObjectAcl('/bos_test2.txt');
        } catch (\Sulao\BaiduBos\Exception $exception) {
        }
        $this->assertEquals('NoSuchKey', $exception->bosCode);
    }

    protected function testGetObjectMeta()
    {
        $meta = $this->client->getObjectMeta('/bos_test.txt');
        $this->assertIsArray($meta);
        $this->assertArrayHasKey('Content-Length', $meta);
        $this->assertEquals(8, $meta['Content-Length']);
    }

    protected function testObjectAcl()
    {
        $this->client->putObjectAcl('/bos_test.txt', 'public-read');
        $acl = $this->client->getObjectAcl('/bos_test.txt');
        $this->assertEquals(
            'READ',
            $acl['accessControlList'][0]['permission'][0]
        );

        $this->client->deleteObjectAcl('/bos_test.txt');
        $exception  = null;
        try {
            $this->client->getObjectAcl('/bos_test.txt');
        } catch (\Sulao\BaiduBos\Exception $exception) {
        }
        $this->assertNotNull($exception);
        $this->assertEquals('ObjectAclNotExists', $exception->bosCode);
    }

    protected function testAppendObject()
    {
        $this->client->appendObject('/bos_test.txt', 'bos test');
        $this->assertEquals(
            'bos test',
            $this->client->getObject('/bos_test.txt')
        );
        $this->client->appendObject(
            '/bos_test.txt',
            '.',
            ['query' => ['offset' => 8]]
        );
        $this->assertEquals(
            'bos test.',
            $this->client->getObject('/bos_test.txt')
        );
    }

    protected function testFetchObject()
    {
        $this->client->fetchObject('/bos_test2.txt', 'https://www.baidu.com');
        $this->assertIsArray($this->client->getObjectMeta('/bos_test2.txt'));
    }

    protected function testListObjects()
    {
        $result = $this->client->listObjects();
        $lists = array_column($result['contents'], 'key');
        $this->assertTrue(in_array('bos_test.txt', $lists));
        $this->assertTrue(in_array('bos_test/bos_test10.txt', $lists));

        $result = $this->client->listObjects(['query' => ['delimiter' => '/']]);
        $lists = array_column($result['contents'], 'key');
        $this->assertTrue(in_array('bos_test.txt', $lists));
        $this->assertFalse(in_array('bos_test/bos_test10.txt', $lists));

        $result = $this->client->listObjects([
            'query' => ['prefix' => 'bos_test/']
        ]);
        $lists = array_column($result['contents'], 'key');
        $this->assertFalse(in_array('bos_test.txt', $lists));
        $this->assertTrue(in_array('bos_test/bos_test10.txt', $lists));
    }

    protected function testDeleteObject()
    {
        $this->client->deleteObject('/bos_test3.txt');
        $exception  = null;
        try {
            $this->client->getObject('/bos_test3.txt');
        } catch (\Sulao\BaiduBos\Exception $exception) {
        }
        $this->assertNotNull($exception);
        $this->assertEquals(404, $exception->getCode());
    }

    protected function testDeleteObjects()
    {
        $this->client->deleteObjects([
            'bos_test.txt',
            'bos_test2.txt',
            'bos_test/bos_test10.txt'
        ]);

        $exception  = null;
        try {
            $this->client->getObjectMeta('/bos_test.txt');
        } catch (\Sulao\BaiduBos\Exception $exception) {
        }
        $this->assertNotNull($exception);
        $this->assertEquals(404, $exception->getCode());

        $exception  = null;
        try {
            $this->client->getObjectMeta('/bos_test2.txt');
        } catch (\Sulao\BaiduBos\Exception $exception) {
        }
        $this->assertNotNull($exception);
        $this->assertEquals(404, $exception->getCode());

        $exception  = null;
        try {
            $this->client->getObjectMeta('/bos_test/bos_test10.txt');
        } catch (\Sulao\BaiduBos\Exception $exception) {
        }
        $this->assertNotNull($exception);
        $this->assertEquals(404, $exception->getCode());
    }
}
