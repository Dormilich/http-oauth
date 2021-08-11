<?php

namespace Dormilich\HttpOauth;

use Dormilich\HttpClient\Exception\RequestException;
use Psr\Http\Message\UriInterface;

/**
 * Get tokens from the OAuth authentication service.
 */
interface TokenClientInterface
{
    /**
     * Request a new OAuth access token. Depending on the setup, an exception
     * may be thrown on error.
     *
     * @param UriInterface $uri Resource target URI.
     * @return TokenInterface
     * @throws RequestException
     */
    public function requestToken(UriInterface $uri): TokenInterface;

    /**
     * Request a new OAuth access token by refreshing the old one.
     *
     * @param TokenInterface $token OAuth2 token.
     * @param UriInterface $uri Resource target URI.
     * @return TokenInterface
     * @throws RequestException
     */
    public function refreshToken(TokenInterface $token, UriInterface $uri): TokenInterface;
}
