<?php

namespace Bacart\GuzzleClient\Client;

use Bacart\Common\Exception\JsonException;
use Bacart\Common\Util\JsonUtils;
use Bacart\GuzzleClient\Exception\GuzzleClientException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use Psr\Http\Message\ResponseInterface;
use Wa72\HtmlPageDom\HtmlPageCrawler;

class GuzzleClient extends Client implements GuzzleClientInterface
{
    /**
     * {@inheritdoc}
     *
     * @param \Traversable|callable[]|null $middlewares
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(array $config = [], \Traversable $middlewares = null)
    {
        $handlerStack = $config['handler'] ?? HandlerStack::create();

        if (null !== $middlewares) {
            foreach ($middlewares as $middleware) {
                $handlerStack->push($middleware);
            }
        }

        parent::__construct($config);
    }

    /**
     * {@inheritdoc}
     */
    public function getGuzzleResponse(
        string $uri,
        array $options = [],
        string $method = GuzzleClientInterface::METHOD_GET
    ): ResponseInterface {
        try {
            return $this->request(
                $method,
                $uri,
                $options
            );
        } catch (GuzzleException $e) {
            throw new GuzzleClientException($e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getGuzzleResponseAsString(
        string $uri,
        array $options = [],
        string $method = GuzzleClientInterface::METHOD_GET
    ): string {
        return (string) $this->getGuzzleResponse(
            $uri,
            $options,
            $method
        )->getBody();
    }

    /**
     * {@inheritdoc}
     */
    public function getGuzzleResponseAsJson(
        string $uri,
        array $options = [],
        string $method = GuzzleClientInterface::METHOD_GET
    ): ?array {
        $stringResponse = $this->getGuzzleResponseAsString(
            $uri,
            $options,
            $method
        );

        try {
            return JsonUtils::jsonDecode($stringResponse, true);
        } catch (JsonException $e) {
            throw new GuzzleClientException($e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getGuzzleResponseAsHtmlPageCrawler(
        string $uri,
        array $options = [],
        string $method = GuzzleClientInterface::METHOD_GET
    ): HtmlPageCrawler {
        $stringResponse = $this->getGuzzleResponseAsString(
            $uri,
            $options,
            $method
        );

        return new HtmlPageCrawler($stringResponse);
    }
}
