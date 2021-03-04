# 百度对象存储

百度对象存储 BOS(Baidu Object Storage) API 针对指定bucket的一些基本操作。

[![MIT licensed](https://img.shields.io/badge/license-MIT-blue.svg)](./LICENSE)
[![Build Status](https://api.travis-ci.org/xinningsu/baidu-bos.svg?branch=master)](https://travis-ci.org/xinningsu/baidu-bos)
[![Coverage Status](https://coveralls.io/repos/github/xinningsu/baidu-bos/badge.svg?branch=master)](https://coveralls.io/github/xinningsu/baidu-bos)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/xinningsu/baidu-bos/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/xinningsu/baidu-bos)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/xinningsu/baidu-bos/badges/code-intelligence.svg?b=master)](https://scrutinizer-ci.com/g/xinningsu/baidu-bos)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=xinningsu_baidu-bos&metric=alert_status)](https://sonarcloud.io/dashboard?id=xinningsu_baidu-bos)
[![Reliability Rating](https://sonarcloud.io/api/project_badges/measure?project=xinningsu_baidu-bos&metric=reliability_rating)](https://sonarcloud.io/dashboard?id=xinningsu_baidu-bos)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=xinningsu_baidu-bos&metric=security_rating)](https://sonarcloud.io/dashboard?id=xinningsu_baidu-bos)
[![Maintainability](https://api.codeclimate.com/v1/badges/e05bc366d89a6159aba2/maintainability)](https://codeclimate.com/github/xinningsu/baidu-bos/maintainability)

# 安装

```
composer require xinningsu/baidu-bos

```

# 例子

```php
require 'vendor/autoload.php';

// 实例化
$client = new \Sulao\BaiduBos\Client(
    'access_key',
    'secret_key',
    'bucket',
    'region'
);

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

# License

[MIT](./LICENSE)
