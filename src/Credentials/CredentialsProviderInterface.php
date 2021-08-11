<?php

namespace Dormilich\HttpOauth\Credentials;

use Dormilich\HttpOauth\Exception\CredentialsNotFoundException;
use Psr\Http\Message\UriInterface;

interface CredentialsProviderInterface
{
    /**
     * Get the credentials for the requested resource.
     *
     * @param UriInterface $uri Resource target protected by OAuth2.
     * @return ClientCredentials
     * @throws CredentialsNotFoundException
     */
    public function get(UriInterface $uri): ClientCredentials;
}
