<?php
namespace ZfeggTest\Psr7Middleware;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Stream;
use Zend\Stratigility\MiddlewareInterface;
use Zfegg\Psr7Middleware\PageCacheMiddleware;

class PageCacheMiddlewareTest extends \PHPUnit_Framework_TestCase
{
    public function testInvoke()
    {
        $cacheItemPool = new ArrayCachePool();
        $request = new ServerRequest([], [], 'http://localhost/{}()/\@:test.json');
        $response = new Response();

        $middleware = new PageCacheMiddleware(
            $cacheItemPool,
            function ($key, $request) {
                return md5($key);
            },
            3
        );

        $callable = $this->createMock(MiddlewareInterface::class);
        $callable->expects($this->once())
            ->method('__invoke')
            ->with($request, $response)
            ->willReturnCallback(function ($request, $response) {
                $response = $response->withHeader('Content-Type', 'application/json');
                $response->getBody()->write('[1,2,3]');

                return $response;
            });
        $resultResponse1 = $middleware($request, $response, $callable);
        $resultResponse2 = $middleware($request, $response->withBody(new Stream('php://temp', 'wb+')), $callable);

        //Call
        $this->assertEquals('application/json', $resultResponse1->getHeaderLine('Content-Type'));
        $this->assertEquals('[1,2,3]', (string)$resultResponse1->getBody());

        //From cache
        $this->assertEquals('application/json', $resultResponse2->getHeaderLine('Content-Type'));
        $this->assertEquals('[1,2,3]', (string)$resultResponse2->getBody());
    }
}
