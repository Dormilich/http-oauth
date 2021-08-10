<?php

namespace Dormilich\HttpOauth;

use function array_filter;
use function array_uintersect;
use function count;
use function date_create;
use function explode;

/**
 * Basic OAuth2 token implementation.
 */
class Token implements TokenInterface
{
    /**
     * @var string[]
     */
    private array $scope = [];

    private string $type;

    private string $access;

    private ?string $refresh = null;

    private ?\DateTimeInterface $expires = null;

    /**
     * @param string $token
     * @param string $type
     */
    public function __construct(string $token, string $type)
    {
        $this->setAccessToken($token);
        $this->setType($type);
    }

    /**
     * @inheritDoc
     */
    public function getAccessToken(): string
    {
        return $this->access;
    }

    public function setAccessToken(string $token): void
    {
        $this->access = $token;
    }

    /**
     * @inheritDoc
     */
    public function getRefreshToken(): ?string
    {
        return $this->refresh;
    }

    public function setRefreshToken(string $token): void
    {
        $this->refresh = $token;
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * Get the scopes of the token.
     *
     * @return string[]
     */
    public function getScope(): array
    {
        return $this->scope;
    }

    /**
     * Test if a token has a given scope.
     *
     * @param string $scope
     * @return bool
     */
    public function hasScope(string $scope): bool
    {
        $match = array_uintersect($this->scope, [$scope], 'strcasecmp');
        return count($match) === 1;
    }

    /**
     * Set the scope of the token.
     *
     * @param string $scope
     */
    public function setScope(string $scope): void
    {
        $values = explode(' ', $scope);
        $this->scope = array_filter($values, 'strlen');
    }

    /**
     * @inheritDoc
     */
    public function getExpiration(): ?\DateTimeInterface
    {
        return $this->expires;
    }

    /**
     * Set the timestamp that the token expires.
     *
     * @param int $timestamp
     */
    public function setExpiration(int $timestamp): void
    {
        $now = new \DateTimeImmutable();
        $this->expires = $now->setTimestamp($timestamp);
    }

    /**
     * @inheritDoc
     */
    public function isExpired(): bool
    {
        if ($this->expires) {
            return date_create() > $this->expires;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->getAccessToken();
    }
}
