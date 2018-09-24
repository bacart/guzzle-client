<?php

namespace Bacart\GuzzleClient\Middleware;

use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LogMiddleware implements GuzzleClientMiddlewareInterface
{
    /** @var MessageFormatter */
    protected $messageFormatter;

    /** @var LoggerInterface|null */
    protected $logger;

    /** @var string */
    protected $logLevel;

    /**
     * @param LoggerInterface|null $logger
     * @param bool                 $debug
     * @param string               $logLevel
     */
    public function __construct(
        LoggerInterface $logger = null,
        bool $debug = false,
        $logLevel = LogLevel::INFO
    ) {
        $template = $debug
            ? MessageFormatter::DEBUG
            : MessageFormatter::CLF;

        $this->messageFormatter = new MessageFormatter($template);
        $this->logger = $logger;
        $this->logLevel = $logLevel;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(callable $handler): callable
    {
        // TODO: remove credentials from logs

        return Middleware::log(
            $this->logger,
            $this->messageFormatter,
            $this->logLevel
        )($handler);
    }
}
