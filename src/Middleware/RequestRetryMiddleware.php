<?php

namespace Bacart\GuzzleClient\Middleware;

use Bacart\GuzzleClient\Client\GuzzleClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RetryMiddleware;
use Psr\Log\LoggerInterface;

class RequestRetryMiddleware implements GuzzleClientMiddlewareInterface
{
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
        $statusCode = null !== $response ? $response->getStatusCode() : 0;

        $retryNeeded = $retries < $this->maxRetries
            && ($exception instanceof ConnectException
                || $statusCode > GuzzleClientInterface::HTTP_INTERNAL_SERVER_ERROR);

        if ($retryNeeded && null !== $this->logger) {
            $context = [
                GuzzleClientMiddlewareInterface::STATUS => $statusCode,
                GuzzleClientMiddlewareInterface::URI    => (string) $request->getUri(),
                GuzzleClientMiddlewareInterface::METHOD => $request->getMethod(),
            ];

            $this->logger->warning(
                sprintf('Request retry (%d)', $retries),
                $context
            );
        }

        return $retryNeeded;
    }
}
