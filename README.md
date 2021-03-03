# 百度对象存储

百度对象存储 BOS(Baidu Object Storage) API 针对指定bucket的一些基本操作。

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
