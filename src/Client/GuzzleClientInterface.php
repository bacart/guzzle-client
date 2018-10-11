<?php

namespace Bacart\GuzzleClient\Client;

use Bacart\GuzzleClient\Exception\GuzzleClientException;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DomCrawler\Crawler;
use Wa72\HtmlPageDom\HtmlPage;

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
    public const METHOD_COPY = 'COPY';
    public const METHOD_MOVE = 'MOVE';
    public const METHOD_LOCK = 'LOCK';
    public const METHOD_UNLOCK = 'UNLOCK';
    public const METHOD_MKCOL = 'MKCOL';
    public const METHOD_MKREDIRECTREF = 'MKREDIRECTREF';
    public const METHOD_UPDATEREDIRECTREF = 'UPDATEREDIRECTREF';

    public const HTTP_CONTINUE = 100;
    public const HTTP_SWITCHING_PROTOCOLS = 101;
    public const HTTP_PROCESSING = 102;            // RFC2518
    public const HTTP_EARLY_HINTS = 103;           // RFC8297
    public const HTTP_OK = 200;
    public const HTTP_CREATED = 201;
    public const HTTP_ACCEPTED = 202;
    public const HTTP_NON_AUTHORITATIVE_INFORMATION = 203;
    public const HTTP_NO_CONTENT = 204;
    public const HTTP_RESET_CONTENT = 205;
    public const HTTP_PARTIAL_CONTENT = 206;
    public const HTTP_MULTI_STATUS = 207;          // RFC4918
    public const HTTP_ALREADY_REPORTED = 208;      // RFC5842
    public const HTTP_IM_USED = 226;               // RFC3229
    public const HTTP_MULTIPLE_CHOICES = 300;
    public const HTTP_MOVED_PERMANENTLY = 301;
    public const HTTP_FOUND = 302;
    public const HTTP_SEE_OTHER = 303;
    public const HTTP_NOT_MODIFIED = 304;
    public const HTTP_USE_PROXY = 305;
    public const HTTP_RESERVED = 306;
    public const HTTP_TEMPORARY_REDIRECT = 307;
    public const HTTP_PERMANENTLY_REDIRECT = 308;  // RFC7238
    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_UNAUTHORIZED = 401;
    public const HTTP_PAYMENT_REQUIRED = 402;
    public const HTTP_FORBIDDEN = 403;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_METHOD_NOT_ALLOWED = 405;
    public const HTTP_NOT_ACCEPTABLE = 406;
    public const HTTP_PROXY_AUTHENTICATION_REQUIRED = 407;
    public const HTTP_REQUEST_TIMEOUT = 408;
    public const HTTP_CONFLICT = 409;
    public const HTTP_GONE = 410;
    public const HTTP_LENGTH_REQUIRED = 411;
    public const HTTP_PRECONDITION_FAILED = 412;
    public const HTTP_REQUEST_ENTITY_TOO_LARGE = 413;
    public const HTTP_REQUEST_URI_TOO_LONG = 414;
    public const HTTP_UNSUPPORTED_MEDIA_TYPE = 415;
    public const HTTP_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    public const HTTP_EXPECTATION_FAILED = 417;
    public const HTTP_I_AM_A_TEAPOT = 418;         // RFC2324
    public const HTTP_MISDIRECTED_REQUEST = 421;   // RFC7540
    public const HTTP_UNPROCESSABLE_ENTITY = 422;  // RFC4918
    public const HTTP_LOCKED = 423;                // RFC4918
    public const HTTP_FAILED_DEPENDENCY = 424;     // RFC4918

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
     * @return Crawler
     */
    public function getGuzzleResponseAsCrawler(
        string $uri,
        array $options = [],
        string $method = self::METHOD_GET
    ): Crawler;

    /**
     * @param string $uri
     * @param array  $options
     * @param string $method
     *
     * @throws GuzzleClientException
     *
     * @return HtmlPage
     */
    public function getGuzzleResponseAsHtmlPage(
        string $uri,
        array $options = [],
        string $method = self::METHOD_GET
    ): HtmlPage;
}
