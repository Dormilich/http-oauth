<?php

namespace Dormilich\HttpOauth\Exception;

/**
 * The authorization grant type is not supported by the authorization server.
 *
 * @link https://datatracker.ietf.org/doc/html/rfc6749#section-5.2
 */
class UnsupportedGrantTypeException extends AuthorisationException
{
}
