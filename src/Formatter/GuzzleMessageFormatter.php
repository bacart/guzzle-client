<?php

namespace Bacart\GuzzleClient\Formatter;

use GuzzleHttp\MessageFormatter;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class GuzzleMessageFormatter extends MessageFormatter
{
    protected const CREDENTIAL_REPLACEMENT = '******';

    /** @var string[] */
    protected $credentialsToReplace;

    /**
     * @param string[] $credentialsToReplace
     * @param bool     $debug
     */
    public function __construct(
        array $credentialsToReplace = [],
        bool $debug = false
    ) {
        foreach ($credentialsToReplace as $credential) {
            $this->credentialsToReplace[$credential] = static::CREDENTIAL_REPLACEMENT;
        }

        $template = $debug
            ? MessageFormatter::DEBUG
            : MessageFormatter::CLF;

        parent::__construct($template);
    }

    /**
     * {@inheritdoc}
     */
    public function format(
        RequestInterface $request,
        ResponseInterface $response = null,
        \Exception $error = null
    ): string {
        return strtr(
            parent::format($request, $response, $error),
            $this->credentialsToReplace
        );
    }
}
