<?php

namespace Dormilich\HttpOauth\Credentials;

use Psr\Http\Message\UriInterface;

/**
 * Provides only one set of credentials for all requests.
 */
class DefaultProvider implements CredentialsProviderInterface
{
    private ClientCredentials $credentials;

    /**
     * @param ClientCredentials $credentials
     */
    public function __construct(ClientCredentials $credentials)
    {
        $this->credentials = $credentials;
    }

    /**
     * @inheritDoc
     */
    public function get(UriInterface $uri): ClientCredentials
    {
        return $this->credentials;
    }
}
