<?php

namespace Dormilich\HttpOauth\Exception;

/**
 * The authenticated client is not authorized to use this authorization grant type.
 *
 * @link https://datatracker.ietf.org/doc/html/rfc6749#section-5.2
 */
class UnauthorizedClientException extends AuthorisationException
{
}
