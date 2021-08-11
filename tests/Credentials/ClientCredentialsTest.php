<?php

namespace Tests\Credentials;

use Dormilich\HttpOauth\Credentials\ClientCredentials;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Dormilich\HttpOauth\Credentials\ClientCredentials
 */
class ClientCredentialsTest extends TestCase
{
    /**
     * @test
     */
    public function set_credentials()
    {
        $credentials = new ClientCredentials('phpunit', '63618739-4d00-4ff1-88f5-bfeaccd81fc5', 'https://example.com/openid');

        $this->assertSame('phpunit', $credentials->getClientId());
        $this->assertSame('63618739-4d00-4ff1-88f5-bfeaccd81fc5', $credentials->getClientSecret());
        $this->assertSame('https://example.com/openid', $credentials->getTokenEndpoint());
        $this->assertFalse($credentials->useBasicAuth());
    }

    /**
     * @test
     */
    public function select_http_basic_for_authorisation()
    {
        $credentials = new ClientCredentials('phpunit', '63618739-4d00-4ff1-88f5-bfeaccd81fc5', 'https://example.com/openid');

        $credentials->setBasicAuth(true);

        $this->assertTrue($credentials->useBasicAuth());

        $credentials->setBasicAuth(false);

        $this->assertFalse($credentials->useBasicAuth());
    }

    /**
     * @test
     */
    public function ignore_basic_auth_for_unsupported_usernames()
    {
        $credentials = new ClientCredentials('foo:bar', '63618739-4d00-4ff1-88f5-bfeaccd81fc5', 'https://example.com/openid');
        // username must not contain ":"
        $credentials->setBasicAuth(true);

        $this->assertFalse($credentials->useBasicAuth());
    }
}
