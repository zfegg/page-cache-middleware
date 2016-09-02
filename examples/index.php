<?php
use Cache\Adapter\Filesystem\FilesystemCachePool;
use Zfegg\Psr7Middleware\PageCacheMiddleware;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

require '../vendor/autoload.php';

$app = new Slim\App(['settings' => ['displayErrorDetails' => 1]]);

$app->get('/', function ($request, $response, $args) {
    $response->write("Welcome to Slim!");
    return $response;
});

$filesystemAdapter = new Local(__DIR__ . '/');
$filesystem = new Filesystem($filesystemAdapter);
$cacheItemPool = new FilesystemCachePool($filesystem);

//Array cache
//$cacheData = [];
//$cacheItemPool = new ArrayCachePool(null, $cacheData);

$middleware = new PageCacheMiddleware(
    $cacheItemPool,
    function ($key, $request) {
        return md5($key);
    }, 3
);

$app->get('/hello[/{name}]', function ($request, \Slim\Http\Response $response, $args) {
    sleep(1);
    $response = $response->withHeader('From-Action', 'true');
    $response->write("Hello, " . $args['name']);
    return $response;
})->setArgument('name', 'World!')
    ->add($middleware)
    ->add(function ($request, \Slim\Http\Response $response, $next) use ($middleware) {
        $cacheProvider = new \Slim\HttpCache\CacheProvider();
        $response = $next($request, $response);
        $data = $middleware->getCacheItem()->get();
        $response = $cacheProvider->withLastModified($response, $data['time']);
        $response = $cacheProvider->withExpires($response, time() + 86400);

        return $response;
    })
    ->add(new \Slim\HttpCache\Cache('public', 86400));

$app->run();
