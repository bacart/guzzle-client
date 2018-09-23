<?php

namespace Bacart\GuzzleClient\Middleware;

interface GuzzleClientMiddlewareInterface
{
    /**
     * @param callable $handler
     *
     * @return \Closure
     */
    public function __invoke(callable $handler): callable;
}
