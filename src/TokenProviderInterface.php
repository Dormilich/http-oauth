<?php

namespace Dormilich\HttpOauth;

use Dormilich\HttpClient\Exception\RequestException;
use Dormilich\HttpOauth\Exception\CredentialsNotFoundException;
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
     * @throws CredentialsNotFoundException
     * @throws RequestException
     */
    public function getToken(UriInterface $uri): TokenInterface;
}
