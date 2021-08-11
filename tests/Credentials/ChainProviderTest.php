<?php

namespace Tests\Credentials;

use Dormilich\HttpOauth\Credentials\ChainProvider;
use Dormilich\HttpOauth\Credentials\ClientCredentials;
use Dormilich\HttpOauth\Credentials\CredentialsProviderInterface;
use Dormilich\HttpOauth\Exception\CredentialsNotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;

/**
 * @covers \Dormilich\HttpOauth\Credentials\ChainProvider
 * @uses \Dormilich\HttpOauth\Credentials\ClientCredentials
 */
class ChainProviderTest extends TestCase
{
    private function uri()
    {
        return $this->createConfiguredMock(UriInterface::class, [
            'getHost' => 'api.example.com',
        ]);
    }

    /**
     * @test
     */
    public function get_credentials_from_provider_list()
    {
        $credentials = new ClientCredentials('phpunit', '63618739-4d00-4ff1-88f5-bfeaccd81fc5', 'https://example.com/openid');

        $provider_1 = $this->createStub(CredentialsProviderInterface::class);
        $provider_1
            ->method('get')
            ->willThrowException(new CredentialsNotFoundException());

        $provider_2 = $this->createStub(CredentialsProviderInterface::class);
        $provider_2
            ->method('get')
            ->willReturn($credentials);

        $provider = new ChainProvider([$provider_1]);
        $provider->add($provider_2);
        $result = $provider->get($this->uri());

        $this->assertSame($credentials, $result);
    }

    /**
     * @test
     */
    public function get_credentials_fails_when_not_found()
    {
        $this->expectException(CredentialsNotFoundException::class);
        $this->expectExceptionMessage('No credentials found for https://api.example.com:8080/resource/item.');

        $provider_1 = $this->createStub(CredentialsProviderInterface::class);
        $provider_1
            ->method('get')
            ->willThrowException(new CredentialsNotFoundException());

        $uri = $this->createConfiguredMock(UriInterface::class, [
            'getScheme' => 'https',
            'getHost' => 'api.example.com',
            'getPort' => 8080,
            'getPath' => '/resource/item',
            'getQuery' => 'foo=bar',
        ]);

        $provider = new ChainProvider();
        $provider->add($provider_1);
        $provider->get($uri);
    }
}
