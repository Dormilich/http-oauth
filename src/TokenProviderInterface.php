<?php

namespace Dormilich\HttpOauth;

use Dormilich\HttpClient\Exception\RequestException;
use Psr\Http\Message\UriInterface;

/**
 * Provide a valid OAuth access token.
 */
interface TokenProviderInterface
{
    /**
     * Return a valid access token.
     *
     * @param UriInterface $uri Resource target that requires authentication.
     * @return TokenInterface
     * @throws RequestException
     */
    public function getToken(UriInterface $uri): TokenInterface;
}
