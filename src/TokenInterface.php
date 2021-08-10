<?php

namespace Dormilich\HttpOauth;

/**
 * Access token interface. According to RFC 6749 the only required fields
 * are `access_token` and `token_type` with a strong recommendation to use the
 * `expires_in` field.
 *
 * @link https://datatracker.ietf.org/doc/html/rfc6749#section-5.1
 */
interface TokenInterface
{
    /**
     * Returns the access token string.
     *
     * @return string
     */
    public function getAccessToken(): string;

    /**
     * Returns the refresh token string.
     *
     * @return string
     */
    public function getRefreshToken(): ?string;

    /**
     * Get the intended way to pass the access token in the request.
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Get the granted scopes for the access token.
     * (printable ASCII without  `"`, `/`, and `~`)
     *
     * @return string[]
     */
    public function getScope(): array;

    /**
     * Get access token expiration date.
     *
     * @return \DateTimeInterface|null
     */
    public function getExpiration(): ?\DateTimeInterface;

    /**
     * Returns TRUE if the access token has expired.
     *
     * @return boolean
     */
    public function isExpired(): bool;

    /**
     * Returns the access token string.
     *
     * @return string
     */
    public function __toString(): string;
}
