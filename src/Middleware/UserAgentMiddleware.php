<?php

namespace Bacart\GuzzleClient\Middleware;

use Psr\Http\Message\RequestInterface;

class UserAgentMiddleware implements GuzzleClientMiddlewareInterface
{
    protected const USER_AGENT_HEADER = 'User-Agent';

    /** @var string[] */
    protected $userAgents;

    /**
     * @param string[] $userAgents
     */
    public function __construct(array $userAgents)
    {
        $this->userAgents = $userAgents;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request) {
            return $request->withHeader(
                static::USER_AGENT_HEADER,
                $this->userAgents[array_rand($this->userAgents)]
            );
        };
    }
}
