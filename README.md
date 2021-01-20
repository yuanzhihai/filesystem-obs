# filesystem-obs

* PHP >= 7.1

## Installation

```
composer require yzh52521/flysystem-obs -vvv
```

## Usage

```php
use League\Flysystem\Filesystem;
use yzh52521\Flysystem\Obs\ObsAdapter;

$config = [
    'key' => 'OBS_ACCESS_ID', // <Your Huawei OBS AccessKeyId>
    'secret' => 'OBS_ACCESS_KEY', // <Your Huawei OBS AccessKeySecret>
    'bucket' => 'OBS_BUCKET', // <OBS bucket name>
    'endpoint' => 'OBS_ENDPOINT', // <the endpoint of OBS, E.g: (https:// or http://).obs.cn-east-2.myhuaweicloud.com | custom domain, E.g:img.abc.com> OBS 外网节点或自定义外部域名
    'cdn_domain' => 'OBS_CDN_DOMAIN', //<CDN domain, cdn域名> 如果isCName为true, getUrl会判断cdnDomain是否设定来决定返回的url，如果cdnDomain未设置，则使用endpoint来生成url，否则使用cdn
    'ssl_verify' => 'OBS_SSL_VERIFY', // <true|false> true to use 'https://' and false to use 'http://'. default is false,
    'debug' => 'APP_DEBUG', // <true|false>
];

$adapter = new ObsAdapter($config);

$flysystem = new League\Flysystem\Filesystem($adapter);

```

## API

```php
bool $flysystem->write('file.md', 'contents');

bool $flysystem->write('file.md', 'http://httpbin.org/robots.txt', ['mime' => 'application/redirect302']);

bool $flysystem->writeStream('file.md', fopen('path/to/your/local/file.jpg', 'r'));

bool $flysystem->update('file.md', 'new contents');

bool $flysystem->updateStream('file.md', fopen('path/to/your/local/file.jpg', 'r'));

bool $flysystem->rename('foo.md', 'bar.md');

bool $flysystem->copy('foo.md', 'foo2.md');

bool $flysystem->delete('file.md');

bool $flysystem->has('file.md');

string|false $flysystem->read('file.md');

array $flysystem->listContents();

array $flysystem->getMetadata('file.md');

int $flysystem->getSize('file.md');

string $flysystem->getAdapter()->getUrl('file.md'); 

string $flysystem->getMimetype('file.md');

int $flysystem->getTimestamp('file.md');
```

## License

MIT
