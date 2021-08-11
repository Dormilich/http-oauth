<?php

namespace Dormilich\HttpOauth\Exception;

/**
 * The requested scope is invalid, unknown, malformed, or exceeds the scope granted
 * by the resource owner.
 *
 * @link https://datatracker.ietf.org/doc/html/rfc6749#section-5.2
 */
class InvalidScopeException extends AuthorisationException
{
}
