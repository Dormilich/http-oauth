<?php

namespace Dormilich\HttpOauth\Exception;

use Dormilich\HttpClient\Exception\DecoderException;

/**
 * Base exception for errors that may be issued for an access token request.
 *
 * @link https://datatracker.ietf.org/doc/html/rfc6749#section-5.2
 */
class AuthorisationException extends DecoderException
{
    /**
     * @var string|null
     */
    private ?string $uri = null;

    /**
     * A URI identifying a human-readable web page with information about the error,
     * used to provide the client developer with additional information about the error.
     *
     * @return string|null
     */
    public function getUri(): ?string
    {
        return $this->uri;
    }

    /**
     * @param string $uri
     */
    public function setUri(string $uri): void
    {
        $this->uri = $uri;
    }
}
