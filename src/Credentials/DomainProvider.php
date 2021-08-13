<?php

namespace Dormilich\HttpOauth\Credentials;

use Dormilich\HttpOauth\Exception\CredentialsNotFoundException;
use Dormilich\HttpOauth\Exception\InvalidUrlException;
use Psr\Http\Message\UriInterface;

use const PHP_URL_HOST;

use function parse_url;
use function strpos;
use function strrev;
use function strtolower;

/**
 * Select a set of credentials based on matching the host.
 */
class DomainProvider implements CredentialsProviderInterface
{
    /**
     * @var array<string,ClientCredentials>
     */
    private array $credentials = [];

    /**
     * @param ClientCredentials $credentials
     * @param string[] $hosts
     * @return self
     * @throws InvalidUrlException
     */
    public function add(ClientCredentials $credentials, iterable $hosts): self
    {
        foreach ($hosts as $host) {
            $key = $this->getHost($host);
            $this->credentials[strrev($key)] = $credentials;
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function get(UriInterface $uri): ClientCredentials
    {
        $rUri = strrev($uri->getHost());

        foreach ($this->credentials as $rHost => $credentials) {
            if (strpos($rUri, $rHost) === 0) {
                return $credentials;
            }
        }

        $host = $uri->getHost();
        throw new CredentialsNotFoundException("No credentials found that correspond to {$host}.");
    }

    /**
     * Normalise the passed host value.
     *
     * @param string $url
     * @return string
     */
    private function getHost(string $url): string
    {
        $url = $this->patchUrl(strtolower($url));
        return parse_url($url, PHP_URL_HOST);
    }

    /**
     * Prepare user-provided URL so the host name can be extracted.
     *
     * @param string $url
     * @return string
     * @throws InvalidUrlException
     */
    private function patchUrl(string $url): string
    {
        if (strpos($url, '://') !== false) {
            return $url;
        }
        if (strpos($url, '//') === 0) {
            return $url;
        }
        if ($url[0] === '/') {
            throw new InvalidUrlException("Missing host in URL {$url}.");
        }
        return '//' . $url;
    }
}
