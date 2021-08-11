<?php

namespace Dormilich\HttpOauth\Credentials;

use function strpos;

class ClientCredentials
{
    private string $id;

    private string $secret;

    private string $uri;

    /**
     * @var bool Whether to authenticate with HTTP Basic Auth (RFC 2617).
     *           Defaults to FALSE since that does not have name restrictions.
     */
    private bool $basic = false;

    /**
     * @param string $client_id OAuth `client_id`
     * @param string $client_secret OAuth `client_secret`
     * @param string $auth_uri OAuth authorisation request URI.
     */
    public function __construct(string $client_id, string $client_secret, string $auth_uri)
    {
        $this->id = $client_id;
        $this->secret = $client_secret;
        $this->uri = $auth_uri;
    }

    /**
     * Get OAuth client id.
     *
     * @return string
     */
    public function getClientId(): string
    {
        return $this->id;
    }

    /**
     * Get OAuth client secret.
     *
     * @return string
     */
    public function getClientSecret(): string
    {
        return $this->secret;
    }

    /**
     * Access token endpoint URL.
     *
     * @return string
     */
    public function getTokenEndpoint(): string
    {
        return $this->uri;
    }

    /**
     * Returns TRUE if HTTP Basic Auth should be used for authentication. If the
     * username contains a colon, disable HTTP Basic Auth (invalid username).
     *
     * @return bool
     */
    public function useBasicAuth(): bool
    {
        return $this->basic;
    }

    /**
     * Set whether HTTP Basic Auth should be used for authentication.
     *
     * @param bool $value
     */
    public function setBasicAuth(bool $value): void
    {
        if (strpos($this->id, ':') === false) {
            $this->basic = $value;
        }
    }
}
