<?php

namespace Tests;

use Dormilich\HttpClient\Exception\RequestException;
use Dormilich\HttpOauth\TokenClientInterface;
use Dormilich\HttpOauth\TokenInterface;
use Dormilich\HttpOauth\TokenProvider;
use Dormilich\HttpOauth\TokenProviderInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;
use Psr\SimpleCache\CacheException;
use Psr\SimpleCache\CacheInterface;

// CacheException does not extend Throwable
class CacheFailure extends \Exception implements CacheException {}

/**
 * @covers \Dormilich\HttpOauth\TokenProvider
 */
class TokenProviderTest extends TestCase
{
    private function token(int $minutes = null)
    {
        $date = is_int($minutes) ? date_create("+{$minutes} minutes") : null;

        return $this->createConfiguredMock(TokenInterface::class, [
            'isExpired' => $minutes < 0,
            'getExpiration' => $date,
        ]);
    }

    private function uri()
    {
        return $this->createConfiguredMock(UriInterface::class, [
            'getHost' => 'example.com',
        ]);
    }

    /**
     * @test
     */
    public function provider_implements_interface()
    {
        $client = $this->createStub(TokenClientInterface::class);
        $cache = $this->createStub(CacheInterface::class);
        $provider = new TokenProvider($client, $cache);

        $this->assertInstanceOf(TokenProviderInterface::class, $provider);
    }

    /**
     * @test
     */
    public function get_token_from_cache()
    {
        $token = $this->token(10);

        $client = $this->createMock(TokenClientInterface::class);
        $client
            ->expects($this->never())
            ->method('requestToken');
        $client
            ->expects($this->never())
            ->method('refreshToken');

        $cache = $this->createMock(CacheInterface::class);
        $cache
            ->expects($this->once())
            ->method('get')
            ->with($this->identicalTo('dormilich-oauth-example.com'))
            ->willReturn($token);

        $uri = $this->uri();

        $provider = new TokenProvider($client, $cache);
        $result = $provider->getToken($uri);

        $this->assertSame($token, $result);
    }

    /**
     * @test
     */
    public function request_new_token_when_cache_is_empty()
    {
        $uri = $this->uri();
        $token = $this->token(10);

        $client = $this->createMock(TokenClientInterface::class);
        $client
            ->expects($this->once())
            ->method('requestToken')
            ->with($this->identicalTo($uri))
            ->willReturn($token);
        $client
            ->expects($this->never())
            ->method('refreshToken');

        $cache = $this->createMock(CacheInterface::class);
        $cache
            ->expects($this->once())
            ->method('get')
            ->with($this->identicalTo('dormilich-oauth-example.com'))
            ->willReturn(null);
        $cache
            ->expects($this->once())
            ->method('set')
            ->with(
                $this->identicalTo('dormilich-oauth-example.com'),
                $this->identicalTo($token),
                $this->identicalTo(600)
            )
            ->willReturn(true);

        $provider = new TokenProvider($client, $cache);
        $result = $provider->getToken($uri);

        $this->assertSame($token, $result);
    }

    /**
     * @test
     */
    public function request_new_token_when_cached_token_is_expired()
    {
        $uri = $this->uri();
        $token = $this->token(10);
        $expired = $this->token(-10);
        $expired
            ->method('getRefreshToken')
            ->willReturn(null);

        $client = $this->createMock(TokenClientInterface::class);
        $client
            ->expects($this->once())
            ->method('requestToken')
            ->with($this->identicalTo($uri))
            ->willReturn($token);
        $client
            ->expects($this->never())
            ->method('refreshToken');

        $cache = $this->createMock(CacheInterface::class);
        $cache
            ->expects($this->once())
            ->method('get')
            ->with($this->identicalTo('dormilich-oauth-example.com'))
            ->willReturn($expired);
        $cache
            ->expects($this->once())
            ->method('set')
            ->with(
                $this->identicalTo('dormilich-oauth-example.com'),
                $this->identicalTo($token),
                $this->identicalTo(600)
            )
            ->willReturn(true);

        $provider = new TokenProvider($client, $cache);
        $result = $provider->getToken($uri);

        $this->assertSame($token, $result);
    }

    /**
     * @test
     */
    public function refresh_token_when_cached_token_is_expired()
    {
        $uri = $this->uri();
        $token = $this->token(10);
        $expired = $this->token(-10);
        $expired
            ->method('getRefreshToken')
            ->willReturn(uniqid());

        $client = $this->createMock(TokenClientInterface::class);
        $client
            ->expects($this->never())
            ->method('requestToken');
        $client
            ->expects($this->once())
            ->method('refreshToken')
            ->with(
                $this->identicalTo($expired),
                $this->identicalTo($uri)
            )
            ->willReturn($token);

        $cache = $this->createMock(CacheInterface::class);
        $cache
            ->expects($this->once())
            ->method('get')
            ->with($this->identicalTo('dormilich-oauth-example.com'))
            ->willReturn($expired);
        $cache
            ->expects($this->once())
            ->method('set')
            ->with(
                $this->identicalTo('dormilich-oauth-example.com'),
                $this->identicalTo($token),
                $this->identicalTo(600)
            )
            ->willReturn(true);

        $provider = new TokenProvider($client, $cache);
        $result = $provider->getToken($uri);

        $this->assertSame($token, $result);
    }

    /**
     * @test
     */
    public function ignore_cache_errors()
    {
        $uri = $this->uri();
        $token = $this->token(10);

        $client = $this->createMock(TokenClientInterface::class);
        $client
            ->expects($this->once())
            ->method('requestToken')
            ->with($this->identicalTo($uri))
            ->willReturn($token);
        $client
            ->expects($this->never())
            ->method('refreshToken');

        $error = new CacheFailure();
        $cache = $this->createMock(CacheInterface::class);
        $cache
            ->expects($this->once())
            ->method('get')
            ->willThrowException($error);
        $cache
            ->expects($this->once())
            ->method('set')
            ->willThrowException($error);

        $provider = new TokenProvider($client, $cache);
        $result = $provider->getToken($uri);

        $this->assertSame($token, $result);
    }

    /**
     * @test
     */
    public function refresh_error_requests_new_token()
    {
        $uri = $this->uri();
        $token = $this->token();
        $expired = $this->token(-10);
        $expired
            ->method('getRefreshToken')
            ->willReturn(uniqid());

        $client = $this->createMock(TokenClientInterface::class);
        $client
            ->expects($this->once())
            ->method('requestToken')
            ->willReturn($token);
        $client
            ->expects($this->once())
            ->method('refreshToken')
            ->willThrowException(new RequestException());

        $cache = $this->createStub(CacheInterface::class);
        $cache
            ->method('get')
            ->willReturn($expired);

        $provider = new TokenProvider($client, $cache);
        $result = $provider->getToken($uri);

        $this->assertSame($token, $result);
    }
}
