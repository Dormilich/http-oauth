<?php

namespace Tests\Credentials;

use Dormilich\HttpOauth\Credentials\ClientCredentials;
use Dormilich\HttpOauth\Credentials\DomainProvider;
use Dormilich\HttpOauth\Exception\CredentialsNotFoundException;
use Dormilich\HttpOauth\Exception\InvalidUrlException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;

/**
 * @covers \Dormilich\HttpOauth\Credentials\DomainProvider
 * @uses \Dormilich\HttpOauth\Credentials\ClientCredentials
 */
class DomainProviderTest extends TestCase
{
    private function credentials()
    {
        return new ClientCredentials('phpunit', '63618739-4d00-4ff1-88f5-bfeaccd81fc5', 'https://example.com/openid');
    }

    private function uri()
    {
        return $this->createConfiguredMock(UriInterface::class, [
            'getHost' => 'api.example.com',
        ]);
    }

    /**
     * @test
     * @testWith ["example.com"]
     *           ["api.example.com"]
     *           ["//example.com"]
     *           ["https://example.com/api"]
     */
    public function matches_defined_host(string $host)
    {
        $credentials = $this->credentials();

        $provider = new DomainProvider();
        $provider->add($credentials, [$host]);
        $result = $provider->get($this->uri());

        $this->assertSame($credentials, $result);
    }

    /**
     * @test
     */
    public function invalid_host_is_rejected()
    {
        $this->expectException(InvalidUrlException::class);
        $this->expectExceptionMessage('Missing host in URL /v1/api/item.');

        $credentials = $this->credentials();

        $provider = new DomainProvider();
        $provider->add($credentials, ['/v1/api/item']);
    }

    /**
     * @test
     */
    public function get_credentials_fails_for_unknown_host()
    {
        $this->expectException(CredentialsNotFoundException::class);
        $this->expectExceptionMessage('No credentials found that correspond to api.example.com.');

        $provider = new DomainProvider();
        $provider->get($this->uri());
    }

    /**
     * @test
     */
    public function select_matching_credentials()
    {
        $credentials_1 = new ClientCredentials('phpunit', uniqid(), 'https://example.com/openid');
        $credentials_2 = new ClientCredentials('test', uniqid(), 'https://example.com/openid');

        $provider = new DomainProvider();
        $provider->add($credentials_1, ['example.org']);
        $provider->add($credentials_2, ['example.com']);

        $result = $provider->get($this->uri());

        $this->assertSame($credentials_2, $result);
    }
}
