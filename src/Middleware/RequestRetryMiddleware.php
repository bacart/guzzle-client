<?php

namespace Bacart\GuzzleClient\Middleware;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RetryMiddleware;
use Psr\Log\LoggerInterface;

class RequestRetryMiddleware implements GuzzleClientMiddlewareInterface
{
    protected const HTTP_INTERNAL_SERVER_ERROR = 500;
    protected const MAX_RETRIES = 2;

    /** @var LoggerInterface|null */
    protected $logger;

    /** @var int */
    protected $maxRetries;

    /**
     * @param LoggerInterface|null $logger
     * @param int                  $maxRetries
     */
    public function __construct(
        LoggerInterface $logger = null,
        int $maxRetries = self::MAX_RETRIES
    ) {
        $this->logger = $logger;
        $this->maxRetries = $maxRetries;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(callable $handler): callable
    {
        return new RetryMiddleware([$this, 'decider'], $handler);
    }

    /**
     * @param int                   $retries
     * @param Request               $request
     * @param Response|null         $response
     * @param RequestException|null $exception
     *
     * @return bool
     */
    public function decider(
        int $retries,
        Request $request,
        Response $response = null,
        RequestException $exception = null
    ): bool {
        if (!($exception instanceof ConnectException)
            || null === $response
            || $response->getStatusCode() < static::HTTP_INTERNAL_SERVER_ERROR) {
            return false;
        }

        $retryNeeded = $retries < $this->maxRetries;

        if ($retryNeeded && null !== $this->logger) {
            $this->logger->warning(
                sprintf('Request retry (%d)', $retries),
                [
                    GuzzleClientMiddlewareInterface::STATUS => null === $response ? 0 : $response->getStatusCode(),
                    GuzzleClientMiddlewareInterface::URI    => (string) $request->getUri(),
                    GuzzleClientMiddlewareInterface::METHOD => $request->getMethod(),
                ]
            );
        }

        return $retryNeeded;
    }
}
