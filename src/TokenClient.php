<?php

namespace Dormilich\HttpOauth;

use Dormilich\HttpClient\Decoder\DecoderInterface;
use Dormilich\HttpClient\Exception\DecoderException;
use Dormilich\HttpClient\Exception\RequestException;
use Dormilich\HttpOauth\Credentials\ClientCredentials;
use Dormilich\HttpOauth\Credentials\CredentialsProviderInterface;
use Dormilich\HttpOauth\Decoder\OauthErrorDecoder;
use Dormilich\HttpOauth\Decoder\TokenDecoder;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriInterface;

use function base64_encode;
use function http_build_query;

/**
 * Request or refresh an OAuth token at the authentication service.
 */
class TokenClient implements TokenClientInterface
{
    private ClientInterface $http;

    private RequestFactoryInterface $requestFactory;

    private StreamFactoryInterface $streamFactory;

    private CredentialsProviderInterface $provider;

    /**
     * @var DecoderInterface[]
     */
    private array $decoder = [];

    /**
     * @param CredentialsProviderInterface $provider
     * @param ClientInterface $http
     * @param RequestFactoryInterface $requestFactory
     * @param StreamFactoryInterface $streamFactory
     */
    public function __construct(
        CredentialsProviderInterface $provider,
        ClientInterface $http,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory
    )
    {
        $this->http = $http;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->provider = $provider;

        $this->setDecoders();
    }

    /**
     * Define the response handlers.
     */
    private function setDecoders(): void
    {
        $this->decoder[] = new TokenDecoder();
        $this->decoder[] = new OauthErrorDecoder();
    }

    /**
     * @inheritDoc
     *
     * @link https://datatracker.ietf.org/doc/html/rfc6749#section-4.4
     */
    public function requestToken(UriInterface $uri): TokenInterface
    {
        $param['grant_type'] = 'client_credentials';

        return $this->submit($param, $uri);
    }

    /**
     * @inheritDoc
     *
     * @link https://datatracker.ietf.org/doc/html/rfc6749#section-6
     */
    public function refreshToken(TokenInterface $token, UriInterface $uri): TokenInterface
    {
        $param['grant_type'] = 'refresh_token';
        $param['refresh_token'] = $token->getRefreshToken();

        return $this->submit($param, $uri);
    }

    /**
     * Request a new access token.
     *
     * @param array $data
     * @param UriInterface $uri
     * @return TokenInterface
     * @throws RequestException
     */
    private function submit(array $data, UriInterface $uri): TokenInterface
    {
        $credentials = $this->provider->get($uri);

        $request = $this->createRequest($data, $credentials);
        $response = $this->doRequest($request);

        try {
            return $this->parseResponse($response);
        } catch (RequestException $e) {
            $e->setRequest($request);
            $e->setResponse($response);
            throw $e;
        }
    }

    /**
     * Create the authorisation request.
     *
     * @link https://datatracker.ietf.org/doc/html/rfc6749#section-2.3
     * @link https://datatracker.ietf.org/doc/html/rfc6749#section-6
     *
     * @param array $data
     * @param ClientCredentials $credentials
     * @return RequestInterface
     */
    private function createRequest(array $data, ClientCredentials $credentials): RequestInterface
    {
        $request = $this->getRequest($credentials);

        if ($credentials->useBasicAuth()) {
            $request = $this->setAuthorisation($request, $credentials);
        } else {
            $data['client_id'] = $credentials->getClientId();
            $data['client_secret'] = $credentials->getClientSecret();
        }

        return $this->setBody($request, $data);
    }

    /**
     * Initialise request object.
     *
     * @param ClientCredentials $credentials
     * @return RequestInterface
     */
    private function getRequest(ClientCredentials $credentials): RequestInterface
    {
        $uri = $credentials->getTokenEndpoint();
        return $this->requestFactory->createRequest('POST', $uri);
    }

    /**
     * Set HTTP Basic authorisation header.
     *
     * @link https://datatracker.ietf.org/doc/html/rfc2617#section-2
     *
     * @param RequestInterface $request
     * @param ClientCredentials $credentials
     * @return RequestInterface
     */
    private function setAuthorisation(RequestInterface $request, ClientCredentials $credentials): RequestInterface
    {
        $authorisation = $credentials->getClientId() . ':' . $credentials->getClientSecret();
        $authorisation = 'Basic ' . base64_encode($authorisation);

        return $request->withHeader('Authorization', $authorisation);
    }

    /**
     * Set request body.
     *
     * @param RequestInterface $request
     * @param array $data
     * @return RequestInterface
     */
    private function setBody(RequestInterface $request, array $data): RequestInterface
    {
        $content = http_build_query($data);
        $stream = $this->streamFactory->createStream($content);

        $request = $request->withBody($stream);
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        return $request;
    }

    /**
     * Run HTTP request.
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws RequestException
     */
    private function doRequest(RequestInterface $request): ResponseInterface
    {
        try {
            return $this->http->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            $http = new RequestException($e->getMessage(), $e->getCode(), $e);
            $http->setRequest($this->getErrorRequest($e, $request));
            throw $http;
        }
    }

    /**
     * Get the request that was eventually sent off.
     *
     * @param ClientExceptionInterface $e
     * @param RequestInterface $request
     * @return RequestInterface
     */
    private function getErrorRequest(ClientExceptionInterface $e, RequestInterface $request): RequestInterface
    {
        if ($e instanceof RequestExceptionInterface) {
            return $e->getRequest();
        }
        if ($e instanceof NetworkExceptionInterface) {
            return $e->getRequest();
        }
        return $request;
    }

    /**
     * Parse the response into an Oauth token.
     *
     * @param ResponseInterface $response
     * @return TokenInterface
     */
    private function parseResponse(ResponseInterface $response): TokenInterface
    {
        foreach ($this->decoder as $decoder) {
            if ($decoder->supports($response)) {
                return $decoder->unserialize($response);
            }
        }

        $message = 'No decoder was found that could handle the authorisation response.';
        throw new DecoderException($message, $response->getStatusCode());
    }
}
