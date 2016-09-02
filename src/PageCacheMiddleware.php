<?php
namespace Zfegg\Psr7Middleware;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class PageCacheMiddleware
{
    /** @var  callable */
    protected $renameKeyFilter;

    protected $ttl = 0;

    /** @var CacheItemPoolInterface */
    protected $cacheItemPool;

    /** @var  \Psr\Cache\CacheItemInterface */
    protected $cacheItem;

    public function __construct(
        CacheItemPoolInterface $cacheItemPool,
        callable $renameKeyFilter = null,
        $ttl = 0
    )
    {
        $this->cacheItemPool = $cacheItemPool;
        $this->setRenameKeyFilter($renameKeyFilter);
        $this->setTtl($ttl);
    }

    /**
     * Page cache.
     * @param Request $request
     * @param Response $response
     * @param callable|null $next
     * @return Response
     */
    public function __invoke(Request $request, Response $response, callable $next = null)
    {
        $key = $request->getUri()->getPath();
        $pool = $this->cacheItemPool;

        if ($renameKeyFilter = $this->getRenameKeyFilter()) {
            $key = $renameKeyFilter($key, $request);
        }

        if ($pool->hasItem($key)) {
            $item = $pool->getItem($key);
            $data = $item->get();

            foreach ($data['headers'] as $key => $header) {
                $response = $response->withHeader($key, implode(',', $header));
            }
            $response->getBody()->write($data['body']);
        } else {
            /** @var \Slim\Http\Response $response */
            $response = $next($request, $response);
            $item = $pool->getItem($key);
            if ($this->ttl) {
                $item->expiresAfter($this->ttl);
            }

            $headers = $response->getHeaders();
            $item->set([
                'time' => time(),
                'ttl' => $this->ttl,
                'headers' => $headers,
                'body' => (string)$response->getBody()
            ]);

            $pool->save($item);
        }

        $this->setCacheItem($item);

        return $response;
    }

    /**
     * @return \Psr\Cache\CacheItemInterface
     */
    public function getCacheItem()
    {
        return $this->cacheItem;
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $cacheItem
     * @return $this
     */
    public function setCacheItem(CacheItemInterface $cacheItem)
    {
        $this->cacheItem = $cacheItem;
        return $this;
    }

    /**
     *
     * @return callable
     */
    public function getRenameKeyFilter()
    {
        return $this->renameKeyFilter;
    }

    /**
     * Set rename cache key filter
     *
     * @param callable $renameKeyFilter
     * @return $this
     */
    public function setRenameKeyFilter($renameKeyFilter)
    {
        $this->renameKeyFilter = $renameKeyFilter;
        return $this;
    }

    /**
     * Get cache item ttl
     * @return int
     */
    public function getTtl()
    {
        return $this->ttl;
    }

    /**
     * Set cache item ttl
     *
     * @param int $ttl
     * @return $this
     */
    public function setTtl($ttl)
    {
        $this->ttl = $ttl;
        return $this;
    }
}
