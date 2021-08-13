<?php

namespace Dormilich\HttpOauth;

use Dormilich\HttpClient\Exception\RequestException;
use Dormilich\HttpOauth\Exception\CredentialsNotFoundException;
use Psr\Http\Message\UriInterface;
use Psr\SimpleCache\CacheException;
use Psr\SimpleCache\CacheInterface;

use function time;

/**
 * Provide a valid OAuth access token abstracting away the means to get such a token.
 */
class TokenProvider implements TokenProviderInterface
{
    private TokenClientInterface $client;

    private CacheInterface $cache;

    /**
     * @param CacheInterface $cache
     * @param TokenClientInterface $client
     */
    public function __construct(TokenClientInterface $client, CacheInterface $cache)
    {
        $this->client = $client;
        $this->cache = $cache;
    }

    /**
     * @inheritDoc
     */
    public function getToken(UriInterface $uri): TokenInterface
    {
        $cache_key = $this->getCacheKey($uri);
        $token = $this->fetchToken($cache_key);

        if (empty($token)) {
            $token = $this->requestToken($uri);
        }
        if ($token->isExpired()) {
            $token = $this->refresh($token, $uri);
        }

        $this->storeToken($cache_key, $token);

        return $token;
    }

    /**
     * @param UriInterface $uri
     * @return string
     * @throws CredentialsNotFoundException
     */
    private function getCacheKey(UriInterface $uri): string
    {
        return 'dormilich-oauth-' . $this->client->getCredentialsId($uri);
    }

    /**
     * Get token from cache, or if there is nothing found, request a new token.
     *
     * @param string $key
     * @return TokenInterface|null
     */
    private function fetchToken(string $key): ?TokenInterface
    {
        try {
            return $this->cache->get($key);
        } catch (CacheException $e) {
            return null; // ignore cache errors
        }
    }

    /**
     * Save requested tokens in the cache.
     *
     * @param string $key
     * @param TokenInterface $token
     */
    private function storeToken(string $key, TokenInterface $token): void
    {
        try {
            $ttl = $this->getTtl($token);
            $this->cache->set($key, $token, $ttl);
        } catch (CacheException $e) {
            return; // ignore cache errors
        }
    }

    /**
     * Attempt to refresh the token.
     *
     * @param TokenInterface $token
     * @param UriInterface $uri
     * @return TokenInterface
     * @throws RequestException
     */
    private function refresh(TokenInterface $token, UriInterface $uri): TokenInterface
    {
        if ($token->getRefreshToken()) {
            return $this->refreshToken($token, $uri);
        } else {
            return $this->requestToken($uri);
        }
    }

    /**
     * Request a new token.
     *
     * @param UriInterface $uri
     * @return TokenInterface
     * @throws RequestException
     */
    private function requestToken(UriInterface $uri): TokenInterface
    {
        return $this->client->requestToken($uri);
    }

    /**
     * Refresh token or fall back to requesting a new token.
     *
     * @param TokenInterface $token
     * @param UriInterface $uri
     * @return TokenInterface
     * @throws RequestException
     */
    private function refreshToken(TokenInterface $token, UriInterface $uri): TokenInterface
    {
        try {
            return $this->client->refreshToken($token, $uri);
        } catch (RequestException $e) {
            return $this->requestToken($uri);
        }
    }

    /**
     * Get the TTL of the token.
     *
     * @param TokenInterface $token
     * @return integer Time in seconds that the token is valid.
     */
    private function getTtl(TokenInterface $token): ?int
    {
        if ($expire = $token->getExpiration() and !$token->isExpired()) {
            return $expire->getTimestamp() - time();
        }

        return null;
    }
}
