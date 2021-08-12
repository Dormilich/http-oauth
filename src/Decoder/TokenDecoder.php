<?php

namespace Dormilich\HttpOauth\Decoder;

use Dormilich\HttpClient\Decoder\ContentTypeTrait;
use Dormilich\HttpClient\Decoder\DecoderInterface;
use Dormilich\HttpClient\Transformer\JsonDecoder;
use Dormilich\HttpClient\Utility\StatusMatcher;
use Dormilich\HttpOauth\Token;
use Dormilich\HttpOauth\TokenInterface;
use Psr\Http\Message\ResponseInterface;

class TokenDecoder implements DecoderInterface
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
     * Test if the request represents a success.
     *
     * @param ResponseInterface $response
     * @return bool
     */
    private function hasStatusCode(ResponseInterface $response): bool
    {
        return StatusMatcher::success()->matches($response->getStatusCode());
    }

    /**
     * @inheritDoc
     */
    public function unserialize(ResponseInterface $response)
    {
        $date = $this->getDate($response);
        $payload = $this->getData($response);

        return $this->createToken($payload, $date);
    }

    /**
     * Create a token instance from the response data.
     *
     * @param \stdClass $data Authorisation token data.
     * @param \DateTimeInterface $date Response timestamp.
     * @return TokenInterface
     */
    private function createToken(\stdClass $data, \DateTimeInterface $date): TokenInterface
    {
        $token = new Token($data->access_token, $data->token_type);

        if (!empty($data->expires_in)) {
            $timestamp = $date->getTimestamp() + $data->expires_in;
            $token->setExpiration($timestamp);
        }
        if (!empty($data->refresh_token)) {
            $token->setRefreshToken($data->refresh_token);
        }
        if (!empty($data->scope)) {
            $token->setScope($data->scope);
        }

        return $token;
    }

    /**
     * Get a datetime instance for the expiration start time.
     *
     * @param ResponseInterface $response
     * @return \DateTimeInterface|null
     */
    private function getDate(ResponseInterface $response): ?\DateTimeInterface
    {
        try {
            $date = $this->getResponseDate($response);
            return new \DateTimeImmutable($date);
        } catch (\Exception $e) {
            return new \DateTimeImmutable();
        }
    }

    /**
     * Get date string from the response header. Use current time if the header
     * was not set.
     *
     * @param ResponseInterface $response
     * @return string
     */
    private function getResponseDate(ResponseInterface $response): string
    {
        if ($response->hasHeader('Date')) {
            return $response->getHeader('Date')[0];
        }
        return 'now';
    }
}
