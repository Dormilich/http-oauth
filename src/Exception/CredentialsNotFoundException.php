<?php

namespace Dormilich\HttpOauth\Exception;

use Dormilich\HttpClient\ExceptionInterface;

/**
 * Thrown when the credentials provider fails to provide a set of credentials.
 */
class CredentialsNotFoundException extends \RuntimeException implements ExceptionInterface
{
}
