Pages cache psr7 middleware / 静态页缓存PSR7中间件
=================================================

[![Build Status](https://travis-ci.org/zfegg/page-cache-middleware.png)](https://travis-ci.org/zfegg/page-cache-middleware)
[![Coverage Status](https://coveralls.io/repos/github/zfegg/page-cache-middleware/badge.svg?branch=master)](https://coveralls.io/github/zfegg/page-cache-middleware?branch=master)
[![Latest Stable Version](https://poser.pugx.org/zfegg/page-cache-middleware/v/stable.png)](https://packagist.org/packages/zfegg/page-cache-middleware)

Cache using psr6.
缓存使用PSR6规范.

常见实例用于缓存程序生成的json,html,xml等

## Installation / 安装

使用 Composer 安装

~~~
$ composer require zfegg/page-cache-middleware
~~~

## Usage / 使用

~~~php
//Array cache
$cacheData = [];
$cacheItemPool = new ArrayCachePool(null, $cacheData);

$middleware = new PageCacheMiddleware(
    $cacheItemPool, //PSR6 cache
    function ($key, $request) {  //Rename cache item key.
        return md5($key);
    },
    60  //Page cache ttl.
);
~~~

明细可参考写的 [slimphp 范例](examples/)

## 有哪些实现了 PSR-6

* [symfony/cache](https://github.com/symfony/cache) 
* [zendframework/zend-cache:dev-develop](https://github.com/zendframework/zend-cache/tree/develop)
* [www.php-cache.com](http://www.php-cache.com/en/latest/)

More [see packagist PSR-6 providers](https://packagist.org/providers/psr/cache-implementation). 

