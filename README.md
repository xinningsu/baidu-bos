# 百度对象存储

百度对象存储 BOS(Baidu Object Storage) API 针对指定bucket的一些基本操作。

[![MIT licensed](https://img.shields.io/badge/license-MIT-blue.svg)](./LICENSE)
[![Build Status](https://scrutinizer-ci.com/g/xinningsu/baidu-bos/badges/build.png?b=master)](https://scrutinizer-ci.com/g/xinningsu/baidu-bos/build-status/master)
[![Code Coverage](https://scrutinizer-ci.com/g/xinningsu/baidu-bos/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/xinningsu/baidu-bos/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/xinningsu/baidu-bos/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/xinningsu/baidu-bos)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/xinningsu/baidu-bos/badges/code-intelligence.svg?b=master)](https://scrutinizer-ci.com/g/xinningsu/baidu-bos)
[![Maintainability](https://api.codeclimate.com/v1/badges/e05bc366d89a6159aba2/maintainability)](https://codeclimate.com/github/xinningsu/baidu-bos/maintainability)

# 安装

```
composer require xinningsu/baidu-bos

```

# 例子

```php
require 'vendor/autoload.php';

// 实例化
$client = new \Sulao\BaiduBos\Client([
    'access_key' => 'access key',
    'secret_key' => 'secret key',
    'bucket' => 'bucket',
    'region' => 'region',
    'options' => ['connect_timeout' => 10] // Optional, guzzle request options
]);

// 添加或更新对象
$client->putObject('/object_name.txt', 'contents');

// 获取对象内容
$content = $client->getObject('/object_name.txt');

// 获取对象Meta信息
$meta = $client->getObjectMeta('/object_name.txt');

// 复制对象
$client->copyObject('/object_name.txt', '/new_object_name.txt');

// 追加数据
$client->appendObject('/object_name.txt', 'more contents');

// URL抓取资源
$client->fetchObject('/object_name.txt', 'https://www.baidu.com');

// 设置ACL
$client->putObjectAcl('/object_name.txt', 'public-read');

// 获取ACL
$acl = $client->getObjectAcl('/object_name.txt');

// 删除ACL
$client->deleteObjectAcl('/object_name.txt');

// 对象列表
$lists = $client->listObjects();

// 删除对象
$client->deleteObject('/object_name.txt');

// 批量删除对象
$client->deleteObjects(['/object_name.txt', '/object_name2.txt']);
```

# 整合

- [Flysystem Baidu BOS](https://github.com/xinningsu/flysystem-baidu-bos)
- [Larvel Filesystem Baidu BOS](https://github.com/xinningsu/laravel-filesystem-baidu-bos)

# 参考

- [https://cloud.baidu.com/doc/BOS/index.html](https://cloud.baidu.com/doc/BOS/index.html)

# License

[MIT](./LICENSE)
