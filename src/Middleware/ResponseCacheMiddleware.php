<?php

namespace Bacart\GuzzleClient\Middleware;

use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Response;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class ResponseCacheMiddleware implements GuzzleClientMiddlewareInterface
{
    public const CACHE = 'cache';

    protected const CACHE_KEY_PREFIX = 'guzzle_cache';

    protected const DEBUG_HEADER = 'X-Guzzle-Cache';

    protected const DEBUG_HEADER_HIT = 'HIT';
    protected const DEBUG_HEADER_MISS = 'MISS';

    protected const URI = 'uri';
    protected const STATUS = 'status';
    protected const METHOD = 'method';
    protected const BODY = 'body';
    protected const HEADERS = 'headers';
    protected const VERSION = 'version';
    protected const REASON = 'reason';

    /** @var CacheItemPoolInterface */
    protected $cache;

    /** @var LoggerInterface|null */
    protected $logger;

    /** @var bool */
    protected $debug;

    /** @var string */
    protected $cacheTtl;

    /**
     * @param CacheItemPoolInterface $cache
     * @param bool                   $debug
     * @param LoggerInterface        $logger
     * @param string                 $cacheTtl
     */
    public function __construct(
        CacheItemPoolInterface $cache,
        bool $debug,
        LoggerInterface $logger = null,
        string $cacheTtl = 'PT1H'
    ) {
        $this->cache = $cache;
        $this->logger = $logger;
        $this->debug = $debug;
        $this->cacheTtl = $cacheTtl;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $cache = $options[static::CACHE] ?? false;

            unset($options[static::CACHE]);

            if (!$cache) {
                return $handler($request, $options)->then(
                    function (ResponseInterface $response) {
                        return $response;
                    }
                );
            }

            $key = $this->getCacheKey($request);
            $cacheItem = $this->cache->getItem($key);

            if (!$cacheItem->isHit()) {
                return $this->cacheSave($handler, $request, $options);
            }

            if (null !== $this->logger) {
                $this->logger->info('Request result was taken from cache');
            }

            $data = $cacheItem->get();

            $response = new Response(
                $data[static::STATUS],
                $data[static::HEADERS],
                $data[static::BODY],
                $data[static::VERSION],
                $data[static::REASON]
            );

            $response = $this->addDebugHeader(
                $response,
                static::DEBUG_HEADER_HIT
            );

            return new FulfilledPromise($response);
        };
    }

    /**
     * @param callable         $handler
     * @param RequestInterface $request
     * @param array            $options
     *
     * @return mixed
     */
    protected function cacheSave(
        callable $handler,
        RequestInterface $request,
        array $options
    ) {
        return $handler($request, $options)->then(
            function (ResponseInterface $response) use ($request) {
                $key = $this->getCacheKey($request);

                $item = $this->cache->getItem($key)
                    ->expiresAfter(new \DateInterval($this->cacheTtl))
                    ->set([
                        static::STATUS  => $response->getStatusCode(),
                        static::HEADERS => $response->getHeaders(),
                        static::BODY    => (string) $response->getBody(),
                        static::VERSION => $response->getProtocolVersion(),
                        static::REASON  => $response->getReasonPhrase(),
                    ]);

                $this->cache->save($item);

                return $this->addDebugHeader(
                    $response,
                    static::DEBUG_HEADER_MISS
                );
            }
        );
    }

    /**
     * @param RequestInterface $request
     *
     * @return string
     */
    protected function getCacheKey(RequestInterface $request): string
    {
        return static::CACHE_KEY_PREFIX.'|'.md5(serialize([
                static::BODY    => (string) $request->getBody(),
                static::HEADERS => $request->getHeaders(),
                static::METHOD  => $request->getMethod(),
                static::URI     => $request->getUri(),
            ]));
    }

    /**
     * @param ResponseInterface $response
     * @param string            $value
     *
     * @return ResponseInterface
     */
    protected function addDebugHeader(
        ResponseInterface $response,
        string $value
    ): ResponseInterface {
        if ($this->debug) {
            try {
                return $response->withHeader(static::DEBUG_HEADER, $value);
            } catch (\InvalidArgumentException $e) {
                if (null !== $this->logger) {
                    $this->logger->error($e->getMessage());
                }
            }
        }

        return $response;
    }
}
