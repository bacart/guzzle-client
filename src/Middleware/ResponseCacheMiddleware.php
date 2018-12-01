<?php

/*
 * This file is part of the Bacart package.
 *
 * (c) Alex Bacart <alex@bacart.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bacart\GuzzleClient\Middleware;

use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use function GuzzleHttp\Psr7\rewind_body;

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
                return $handler($request, $options);
            }

            $key = $this->getCacheItemKey($request);
            $cacheItem = $this->cache->getItem($key);

            if (!$cacheItem->isHit()) {
                return $handler($request, $options)->then(
                    function (ResponseInterface $response) use ($request): ResponseInterface {
                        return $this->saveToCache($request, $response)
                            ? $this->addDebugHeader($response, static::DEBUG_HEADER_MISS)
                            : $response;
                    }
                );
            }

            if (null !== $this->logger) {
                $this->logger->info('Guzzle request result is taken from cache', [
                    GuzzleClientMiddlewareInterface::URI => (string) $request->getUri(),
                ]);
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
     * @param RequestInterface $request
     *
     * @return string
     */
    protected function getCacheItemKey(RequestInterface $request): string
    {
        $body = (string) $request->getBody();
        rewind_body($request);

        return static::CACHE_KEY_PREFIX.'|'.md5(serialize([
            GuzzleClientMiddlewareInterface::BODY    => $body,
            GuzzleClientMiddlewareInterface::HEADERS => $request->getHeaders(),
            GuzzleClientMiddlewareInterface::METHOD  => $request->getMethod(),
            GuzzleClientMiddlewareInterface::URI     => (string) $request->getUri(),
        ]));
    }

    /**
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     *
     * @return bool
     */
    protected function saveToCache(
        RequestInterface $request,
        ResponseInterface $response
    ): bool {
        $key = $this->getCacheItemKey($request);

        try {
            $body = (string) $response->getBody();
            rewind_body($response);

            $cacheItem = $this
                ->cache
                ->getItem($key)
                ->expiresAfter(new \DateInterval($this->cacheTtl))
                ->set([
                    GuzzleClientMiddlewareInterface::BODY    => $body,
                    GuzzleClientMiddlewareInterface::HEADERS => $response->getHeaders(),
                    GuzzleClientMiddlewareInterface::STATUS  => $response->getStatusCode(),
                    GuzzleClientMiddlewareInterface::REASON  => $response->getReasonPhrase(),
                    GuzzleClientMiddlewareInterface::VERSION => $response->getProtocolVersion(),
                ]);
        } catch (InvalidArgumentException | \Exception $e) {
            if (null !== $this->logger) {
                $this->logger->error($e->getMessage());
            }

            return false;
        }

        $result = $this->cache->save($cacheItem);

        if ($result && null !== $this->logger) {
            $this->logger->info('Guzzle request result is saved to cache', [
                GuzzleClientMiddlewareInterface::URI => (string) $request->getUri(),
            ]);
        }

        return $result;
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
