<?php

namespace Dormilich\HttpOauth\Exception;

/**
 * The request is missing a required parameter, includes an unsupported parameter
 * value (other than grant type), repeats a parameter, includes multiple credentials,
 * utilizes more than one mechanism for authenticating the client, or is otherwise malformed.
 *
 * @link https://datatracker.ietf.org/doc/html/rfc6749#section-5.2
 */
class InvalidRequestException extends AuthorisationException
{
}
