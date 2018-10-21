<?php

namespace Bacart\GuzzleClient\Client;

use Bacart\Common\Exception\JsonException;
use Bacart\Common\Exception\MissingPackageException;
use Bacart\Common\Util\JsonUtils;
use Bacart\GuzzleClient\Exception\GuzzleClientException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DomCrawler\Crawler;
use Wa72\HtmlPageDom\HtmlPage;

class GuzzleClient extends Client implements GuzzleClientInterface
{
    /**
     * {@inheritdoc}
     *
     * @param iterable|callable[]|null $middlewares
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(
        array $config = [],
        iterable $middlewares = null
    ) {
        if (null !== $middlewares) {
            $handlerStack = $config[GuzzleClientInterface::CONFIG_HANDLER]
                ?? HandlerStack::create();

            foreach ($middlewares as $middleware) {
                $handlerStack->push($middleware);
            }

            $config[GuzzleClientInterface::CONFIG_HANDLER] = $handlerStack;
        }

        parent::__construct($config);
    }

    /**
     * {@inheritdoc}
     *
     * @throws GuzzleClientException
     *
     * @psalm-suppress InvalidCatch
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
     *
     * @throws GuzzleClientException
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
     *
     * @throws GuzzleClientException
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
     *
     * @throws MissingPackageException
     * @throws GuzzleClientException
     */
    public function getGuzzleResponseAsCrawler(
        string $uri,
        array $options = [],
        string $method = GuzzleClientInterface::METHOD_GET
    ): Crawler {
        if (!class_exists('Symfony\Component\DomCrawler\Crawler')) {
            throw new MissingPackageException('symfony/dom-crawler');
        }

        $stringResponse = $this->getGuzzleResponseAsString(
            $uri,
            $options,
            $method
        );

        $baseHref = trim($uri, '/');
        $baseUri = rtrim(
            $this->getConfig(GuzzleClientInterface::BASE_URI),
            '/'
        );

        return new Crawler(
            $stringResponse,
            $baseUri.'/'.$baseHref,
            $uri
        );
    }

    /**
     * {@inheritdoc}
     *
     * @throws MissingPackageException
     * @throws GuzzleClientException
     */
    public function getGuzzleResponseAsHtmlPage(
        string $uri,
        array $options = [],
        string $method = GuzzleClientInterface::METHOD_GET
    ): HtmlPage {
        if (!class_exists('Wa72\HtmlPageDom\HtmlPage')) {
            throw new MissingPackageException('wa72/htmlpagedom');
        }

        $stringResponse = $this->getGuzzleResponseAsString(
            $uri,
            $options,
            $method
        );

        return new HtmlPage(
            $stringResponse,
            $uri
        );
    }
}
