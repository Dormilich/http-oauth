<?php

namespace Tests\Credentials;

use Dormilich\HttpOauth\Credentials\ClientCredentials;
use Dormilich\HttpOauth\Credentials\DefaultProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;

/**
 * @covers \Dormilich\HttpOauth\Credentials\DefaultProvider
 * @uses \Dormilich\HttpOauth\Credentials\ClientCredentials
 */
class DefaultProviderTest extends TestCase
{
    /**
     * @test
     */
    public function get_credentials()
    {
        $credentials = new ClientCredentials('phpunit', '63618739-4d00-4ff1-88f5-bfeaccd81fc5', 'https://example.com/openid');
        $provider = new DefaultProvider($credentials);
        $uri = $this->createStub(UriInterface::class);
        $set = $provider->get($uri);

        $this->assertSame($credentials, $set);
    }

    /**
     * @test
     */
    public function provider_always_returns_same_credentials()
    {
        $credentials = new ClientCredentials('phpunit', '63618739-4d00-4ff1-88f5-bfeaccd81fc5', 'https://example.com/openid');
        $provider = new DefaultProvider($credentials);

        $uri_1 = $this->createStub(UriInterface::class);
        $uri_2 = $this->createStub(UriInterface::class);

        $set_1 = $provider->get($uri_1);
        $set_2 = $provider->get($uri_2);

        $this->assertNotSame($uri_1, $uri_2);
        $this->assertSame($set_1, $set_2);
    }
}
