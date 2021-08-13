<?php

namespace Dormilich\HttpOauth\Encoder;

use Dormilich\HttpClient\Encoder\EncoderInterface;
use Dormilich\HttpClient\Exception\RequestException;
use Dormilich\HttpOauth\Exception\CredentialsNotFoundException;
use Dormilich\HttpOauth\TokenProviderInterface;
use Psr\Http\Message\RequestInterface;

use function ucfirst;

class AuthorisationEncoder implements EncoderInterface
{
    private TokenProviderInterface $provider;

    /**
     * @param TokenProviderInterface $provider
     */
    public function __construct(TokenProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @inheritDoc
     */
    public function supports($data): bool
    {
        return $data instanceof RequestInterface and !$data->hasHeader('Authorization');
    }

    /**
     * @inheritDoc
     */
    public function getContentType(): ?string
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function serialize(RequestInterface $request, $data): RequestInterface
    {
        try {
            return $request->withHeader('Authorization', $this->getAuthorisation($request));
        } catch (CredentialsNotFoundException $e) {
            return $request;
        }
    }

    /**
     * @param RequestInterface $request
     * @return string
     * @throws RequestException
     * @throws CredentialsNotFoundException
     */
    private function getAuthorisation(RequestInterface $request): string
    {
        $oauth = $this->provider->getToken($request->getUri());

        $type  = $oauth->getType();
        $token = $oauth->getAccessToken();

        return ucfirst($type) . ' ' . $token;
    }
}
