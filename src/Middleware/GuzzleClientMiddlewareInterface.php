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
