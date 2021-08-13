<?php

namespace Dormilich\HttpOauth;

use Dormilich\HttpClient\Exception\RequestException;
use Dormilich\HttpOauth\Exception\CredentialsNotFoundException;
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
     * @throws CredentialsNotFoundException
     * @throws RequestException
     */
    public function requestToken(UriInterface $uri): TokenInterface;

    /**
     * Request a new OAuth access token by refreshing the old one.
     *
     * @param TokenInterface $token OAuth2 token.
     * @param UriInterface $uri Resource target URI.
     * @return TokenInterface
     * @throws CredentialsNotFoundException
     * @throws RequestException
     */
    public function refreshToken(TokenInterface $token, UriInterface $uri): TokenInterface;

    /**
     * Get an identifier that can be associated with the access token, e.g. for caching.
     *
     * @param UriInterface $uri
     * @return string
     * @throws CredentialsNotFoundException
     */
    public function getCredentialsId(UriInterface $uri): string;
}
