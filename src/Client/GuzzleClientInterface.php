<?php

namespace Bacart\GuzzleClient\Client;

use Bacart\GuzzleClient\Exception\GuzzleClientException;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Wa72\HtmlPageDom\HtmlPageCrawler;

interface GuzzleClientInterface extends ClientInterface
{
    public const CONFIG_HANDLER = 'handler';
    public const BASE_URI = 'base_uri';

    public const METHOD_HEAD = 'HEAD';
    public const METHOD_GET = 'GET';
    public const METHOD_POST = 'POST';
    public const METHOD_PUT = 'PUT';
    public const METHOD_PATCH = 'PATCH';
    public const METHOD_DELETE = 'DELETE';
    public const METHOD_PURGE = 'PURGE';
    public const METHOD_OPTIONS = 'OPTIONS';
    public const METHOD_TRACE = 'TRACE';
    public const METHOD_CONNECT = 'CONNECT';
    public const METHOD_PROPFIND = 'PROPFIND';
    public const METHOD_PROPPATCH = 'PROPPATCH';
    public const METHOD_MKCOL = 'MKCOL';

    /**
     * @param string $uri
     * @param array  $options
     * @param string $method
     *
     * @throws GuzzleClientException
     *
     * @return ResponseInterface
     */
    public function getGuzzleResponse(
        string $uri,
        array $options = [],
        string $method = self::METHOD_GET
    ): ResponseInterface;

    /**
     * @param string $uri
     * @param array  $options
     * @param string $method
     *
     * @throws GuzzleClientException
     *
     * @return string
     */
    public function getGuzzleResponseAsString(
        string $uri,
        array $options = [],
        string $method = self::METHOD_GET
    ): string;

    /**
     * @param string $uri
     * @param array  $options
     * @param string $method
     *
     * @throws GuzzleClientException
     *
     * @return array|null
     */
    public function getGuzzleResponseAsJson(
        string $uri,
        array $options = [],
        string $method = self::METHOD_GET
    ): ?array;

    /**
     * @param string $uri
     * @param array  $options
     * @param string $method
     *
     * @throws GuzzleClientException
     *
     * @return HtmlPageCrawler
     */
    public function getGuzzleResponseAsHtmlPageCrawler(
        string $uri,
        array $options = [],
        string $method = self::METHOD_GET
    ): HtmlPageCrawler;
}
