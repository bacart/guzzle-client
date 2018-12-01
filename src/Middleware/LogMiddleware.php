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

use Bacart\GuzzleClient\Formatter\GuzzleMessageFormatter;
use GuzzleHttp\Middleware;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LogMiddleware implements GuzzleClientMiddlewareInterface
{
    /** @var GuzzleMessageFormatter */
    protected $formatter;

    /** @var LoggerInterface|null */
    protected $logger;

    /** @var string */
    protected $logLevel;

    /**
     * @param GuzzleMessageFormatter $formatter
     * @param LoggerInterface|null   $logger
     * @param string                 $logLevel
     */
    public function __construct(
        GuzzleMessageFormatter $formatter,
        LoggerInterface $logger = null,
        $logLevel = LogLevel::INFO
    ) {
        $this->formatter = $formatter;
        $this->logger = $logger;
        $this->logLevel = $logLevel;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(callable $handler): callable
    {
        if (null === $this->logger) {
            return $handler;
        }

        return Middleware::log(
            $this->logger,
            $this->formatter,
            $this->logLevel
        )($handler);
    }
}
