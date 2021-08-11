<?php

namespace Dormilich\HttpOauth\Credentials;

use Dormilich\HttpOauth\Exception\CredentialsNotFoundException;
use Psr\Http\Message\UriInterface;

/**
 * Iterates through all providers to get a set of credentials.
 */
class ChainProvider implements CredentialsProviderInterface
{
    use UrlTrait;

    /**
     * @var CredentialsProviderInterface[]
     */
    private array $providers = [];

    /**
     * @param CredentialsProviderInterface[] $providers
     */
    public function __construct(array $providers = [])
    {
        foreach ($providers as $provider) {
            $this->add($provider);
        }
    }

    /**
     * @param CredentialsProviderInterface $provider
     */
    public function add(CredentialsProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * @inheritDoc
     */
    public function get(UriInterface $uri): ClientCredentials
    {
        foreach ($this->providers as $provider) {
            try {
                return $provider->get($uri);
            } catch (CredentialsNotFoundException $e) {
                continue;
            }
        }

        $url = $this->getUrl($uri);
        throw new CredentialsNotFoundException("No credentials found for {$url}.");
    }
}
