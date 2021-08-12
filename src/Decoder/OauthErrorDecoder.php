<?php

namespace Dormilich\HttpOauth\Decoder;

use Dormilich\HttpClient\Decoder\ContentTypeTrait;
use Dormilich\HttpClient\Decoder\DecoderInterface;
use Dormilich\HttpClient\Transformer\JsonDecoder;
use Dormilich\HttpClient\Utility\StatusMatcher;
use Dormilich\HttpOauth\Exception\AuthorisationException;
use Psr\Http\Message\ResponseInterface;

use function class_exists;
use function str_replace;
use function ucwords;

/**
 * Parse the OAuth error response and throw a matching exception.
 */
class OauthErrorDecoder implements DecoderInterface
{
    use ContentTypeTrait;
    use TransformationTrait;

    public function __construct()
    {
        $this->setTransformer(new JsonDecoder());
    }

    /**
     * @inheritDoc
     */
    public function supports(ResponseInterface $response): bool
    {
        return $this->hasStatusCode($response) and $this->hasContentType($response);
    }

    /**
     * Test if the request represents a client error.
     *
     * @param ResponseInterface $response
     * @return bool
     */
    private function hasStatusCode(ResponseInterface $response): bool
    {
        return StatusMatcher::clientError()->matches($response->getStatusCode());
    }

    /**
     * @inheritDoc
     */
    public function unserialize(ResponseInterface $response)
    {
        $data = $this->getData($response);
        $error = $this->getException($data, $response->getStatusCode());
        if (!empty($data->error_uri)) {
            $error->setUri($data->error_uri);
        }
        throw $error;
    }

    /**
     * Build exception from the error response.
     *
     * @param \stdClass $payload
     * @param integer $status HTTP status code.
     * @return AuthorisationException
     */
    private function getException(\stdClass $payload, int $status): AuthorisationException
    {
        $exception = $this->getExceptionClass($payload);
        $message = $this->getExceptionMessage($payload);

        return new $exception($message, $status);
    }

    /**
     * Create exception according to the OAuth error code.
     *
     * @param \stdClass $payload
     * @return string
     */
    private function getExceptionClass(\stdClass $payload): string
    {
        $ns = $this->getExceptionNamespace();
        $name = $this->getExceptionName($payload->error);

        $class = $ns . '\\' . $name;
        $base = AuthorisationException::class;
        return class_exists($class) ? $class : $base;
    }

    /**
     * Get the error description, if available.
     *
     * @param \stdClass $payload
     * @return string
     */
    private function getExceptionMessage(\stdClass $payload): string
    {
        if (empty($payload->error_description)) {
            return $payload->error;
        }
        return $payload->error_description;
    }

    /**
     * Get namespace of the exceptions.
     *
     * @return string
     */
    private function getExceptionNamespace(): string
    {
        return (new \ReflectionClass(AuthorisationException::class))->getNamespaceName();
    }

    /**
     * Convert OAuth error into exception class name.
     *
     * @param string $name
     * @return string
     */
    private function getExceptionName(string $name): string
    {
        $name = ucwords($name . '_exception', '_');
        $name = str_replace('_', '', $name);

        return $name;
    }
}
