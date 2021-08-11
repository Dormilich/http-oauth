<?php

namespace Tests\Decoder;

use Dormilich\HttpOauth\Decoder\OauthErrorDecoder;
use Dormilich\HttpOauth\Exception\AuthorisationException;
use Dormilich\HttpOauth\Exception\InvalidClientException;
use Dormilich\HttpOauth\Exception\InvalidGrantException;
use Dormilich\HttpOauth\Exception\InvalidRequestException;
use Dormilich\HttpOauth\Exception\InvalidScopeException;
use Dormilich\HttpOauth\Exception\UnauthorizedClientException;
use Dormilich\HttpOauth\Exception\UnsupportedGrantTypeException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @covers \Dormilich\HttpOauth\Decoder\OauthErrorDecoder
 * @covers \Dormilich\HttpOauth\Exception\AuthorisationException
 */
class OauthErrorDecoderTest extends TestCase
{
    private function response(array $data)
    {
        $json = json_encode($data);
        $body = $this->createConfiguredMock(StreamInterface::class, [
            '__toString' => $json,
            'getContents' => $json,
            'getSize' => strlen($json),
        ]);

        return $this->createConfiguredMock(ResponseInterface::class, [
            'getStatusCode' => 400,
            'getBody' => $body,
        ]);
    }

    /**
     * @test
     */
    public function decoder_supports_client_error_responses()
    {
        $response = $this->createConfiguredMock(ResponseInterface::class, [
            'getStatusCode' => 400,
            'getHeaderLine' => 'application/json',
        ]);

        $decoder = new OauthErrorDecoder();

        $this->assertTrue($decoder->supports($response));
    }

    /**
     * @test
     */
    public function decoder_ignores_server_error_responses()
    {
        $response = $this->createConfiguredMock(ResponseInterface::class, [
            'getStatusCode' => 500,
            'getHeaderLine' => 'application/json',
        ]);

        $decoder = new OauthErrorDecoder();

        $this->assertFalse($decoder->supports($response));
    }

    /**
     * @test
     */
    public function decoder_ignores_success_responses()
    {
        $response = $this->createConfiguredMock(ResponseInterface::class, [
            'getStatusCode' => 200,
            'getHeaderLine' => 'application/json',
        ]);

        $decoder = new OauthErrorDecoder();

        $this->assertFalse($decoder->supports($response));
    }

    /**
     * @test
     * @testWith [""]
     *           ["application/xml"]
     *           ["text/plain"]
     */
    public function decoder_ignores_non_json_responses(string $type)
    {
        $response = $this->createConfiguredMock(ResponseInterface::class, [
            'getStatusCode' => 400,
            'getHeaderLine' => $type,
        ]);

        $decoder = new OauthErrorDecoder();

        $this->assertFalse($decoder->supports($response));
        $this->assertSame('application/json', $decoder->getContentType());
    }

    /**
     * @test
     */
    public function process_invalid_request_error()
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('invalid_request');

        $data['error'] = 'invalid_request';
        $response = $this->response($data);

        $decoder = new OauthErrorDecoder();
        $decoder->unserialize($response);
    }

    /**
     * @test
     */
    public function process_invalid_client_error()
    {
        $this->expectException(InvalidClientException::class);
        $this->expectExceptionMessage('invalid_client');

        $data['error'] = 'invalid_client';
        $response = $this->response($data);

        $decoder = new OauthErrorDecoder();
        $decoder->unserialize($response);
    }

    /**
     * @test
     */
    public function process_invalid_grant_error()
    {
        $this->expectException(InvalidGrantException::class);
        $this->expectExceptionMessage('invalid_grant');

        $data['error'] = 'invalid_grant';
        $response = $this->response($data);

        $decoder = new OauthErrorDecoder();
        $decoder->unserialize($response);
    }

    /**
     * @test
     */
    public function process_unauthorized_client_error()
    {
        $this->expectException(UnauthorizedClientException::class);
        $this->expectExceptionMessage('unauthorized_client');

        $data['error'] = 'unauthorized_client';
        $response = $this->response($data);

        $decoder = new OauthErrorDecoder();
        $decoder->unserialize($response);
    }

    /**
     * @test
     */
    public function process_unsupported_grant_type_error()
    {
        $this->expectException(UnsupportedGrantTypeException::class);
        $this->expectExceptionMessage('unsupported_grant_type');

        $data['error'] = 'unsupported_grant_type';
        $response = $this->response($data);

        $decoder = new OauthErrorDecoder();
        $decoder->unserialize($response);
    }

    /**
     * @test
     */
    public function process_invalid_scope_error()
    {
        $this->expectException(InvalidScopeException::class);
        $this->expectExceptionMessage('invalid_scope');

        $data['error'] = 'invalid_scope';
        $response = $this->response($data);

        $decoder = new OauthErrorDecoder();
        $decoder->unserialize($response);
    }

    /**
     * @test
     */
    public function process_other_error()
    {
        $this->expectException(AuthorisationException::class);
        $this->expectExceptionMessage('Authorisation server does not support scopes');

        $data['error'] = 'unsupported_scope';
        $data['error_description'] = 'Authorisation server does not support scopes';
        $data['error_uri'] = 'https://example.com/error/unsupported_scope';
        $response = $this->response($data);

        try {
            $decoder = new OauthErrorDecoder();
            $decoder->unserialize($response);
        } catch (AuthorisationException $e) {
            $this->assertSame('https://example.com/error/unsupported_scope', $e->getUri());
            throw $e;
        }
    }
}
