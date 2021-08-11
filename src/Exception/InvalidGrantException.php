<?php

namespace Dormilich\HttpOauth\Exception;

/**
 * The provided authorization grant (e.g., authorization code, resource owner credentials)
 * or refresh token is invalid, expired, revoked, does not match the redirection
 * URI used in the authorization request, or was issued to another client.
 *
 * @link https://datatracker.ietf.org/doc/html/rfc6749#section-5.2
 */
class InvalidGrantException extends AuthorisationException
{
}
