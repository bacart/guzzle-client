<?php

namespace Bacart\GuzzleClient\Middleware;

use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class ResponseCacheMiddleware implements GuzzleClientMiddlewareInterface
{
    public const CACHE = 'cache';
    public const CACHE_TTL = 'PT1H';

    protected const CACHE_KEY_PREFIX = 'guzzle_cache';

    protected const DEBUG_HEADER = 'X-Guzzle-Cache';

    protected const DEBUG_HEADER_HIT = 'HIT';
    protected const DEBUG_HEADER_MISS = 'MISS';

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
     * @param LoggerInterface|null   $logger
     * @param bool                   $debug
     * @param string                 $cacheTtl
     */
    public function __construct(
        CacheItemPoolInterface $cache,
        LoggerInterface $logger = null,
        bool $debug = false,
        string $cacheTtl = self::CACHE_TTL
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
        return function (RequestInterface $request, array $options) use ($handler): PromiseInterface {
            $cache = $options[static::CACHE] ?? true;

            unset($options[static::CACHE]);

            if (!$cache) {
                return $handler($request, $options)->then(
                    function (ResponseInterface $response): ResponseInterface {
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
                $data[GuzzleClientMiddlewareInterface::STATUS],
                $data[GuzzleClientMiddlewareInterface::HEADERS],
                $data[GuzzleClientMiddlewareInterface::BODY],
                $data[GuzzleClientMiddlewareInterface::VERSION],
                $data[GuzzleClientMiddlewareInterface::REASON]
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
            function (ResponseInterface $response) use ($request): ResponseInterface {
                $key = $this->getCacheKey($request);

                $item = $this->cache->getItem($key)
                    ->expiresAfter(new \DateInterval($this->cacheTtl))
                    ->set([
                        GuzzleClientMiddlewareInterface::STATUS  => $response->getStatusCode(),
                        GuzzleClientMiddlewareInterface::HEADERS => $response->getHeaders(),
                        GuzzleClientMiddlewareInterface::BODY    => (string) $response->getBody(),
                        GuzzleClientMiddlewareInterface::VERSION => $response->getProtocolVersion(),
                        GuzzleClientMiddlewareInterface::REASON  => $response->getReasonPhrase(),
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
            GuzzleClientMiddlewareInterface::BODY    => (string) $request->getBody(),
            GuzzleClientMiddlewareInterface::HEADERS => $request->getHeaders(),
            GuzzleClientMiddlewareInterface::METHOD  => $request->getMethod(),
            GuzzleClientMiddlewareInterface::URI     => (string) $request->getUri(),
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
