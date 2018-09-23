<?php

namespace Bacart\GuzzleClient\Middleware;

interface GuzzleClientMiddlewareInterface
{
    public const URI = 'uri';
    public const STATUS = 'status';
    public const METHOD = 'method';
    public const BODY = 'body';
    public const HEADERS = 'headers';
    public const VERSION = 'version';
    public const REASON = 'reason';

    /**
     * @param callable $handler
     *
     * @return \Closure
     */
    public function __invoke(callable $handler): callable;
}
