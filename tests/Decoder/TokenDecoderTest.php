<?php

namespace Tests\Decoder;

use Dormilich\HttpOauth\Decoder\TokenDecoder;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @covers \Dormilich\HttpOauth\Decoder\TokenDecoder
 * @uses \Dormilich\HttpOauth\Token
 */
class TokenDecoderTest extends TestCase
{
    private function response()
    {
        $response = $this->createStub(ResponseInterface::class);
        $response
            ->method('getStatusCode')
            ->willReturn(200);
        $response
            ->method('getBody')
            ->willReturn($this->body());
        $response
            ->method('getHeaderLine')
            ->willReturnMap([['Content-Type', 'application/json']]);
        $response
            ->method('getHeader')
            ->willReturnMap([['Date', [date(DATE_RFC7231)]]]);
        $response
            ->method('hasHeader')
            ->willReturnMap([['Date', true]]);

        return $response;
    }

    private function body()
    {
        $default['token_type'] = 'bearer';
        $default['access_token'] = 'b6d8bd06-a1e1-4e81-9a1b-e230b17a3cb8';
        $default['refresh_token'] = 'a47f3aa1-d6d7-4d93-ab21-10fbc13250b7';
        $default['expires_in'] = 600;
        $default['scope'] = 'foo bar';

        $json = json_encode($default);

        return $this->createConfiguredMock(StreamInterface::class, [
            '__toString' => $json,
            'getContents' => $json,
            'getSize' => strlen($json),
        ]);
    }

    /**
     * @test
     */
    public function decoder_supports_json_responses()
    {
        $response = $this->createConfiguredMock(ResponseInterface::class, [
            'getStatusCode' => 200,
            'getHeaderLine' => 'application/json',
        ]);

        $decoder = new TokenDecoder();

        $this->assertTrue($decoder->supports($response));
    }

    /**
     * @test
     */
    public function decoder_ignores_error_responses()
    {
        $response = $this->createConfiguredMock(ResponseInterface::class, [
            'getStatusCode' => 400,
            'getHeaderLine' => 'application/json',
        ]);

        $decoder = new TokenDecoder();

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
            'getStatusCode' => 200,
            'getHeaderLine' => $type,
        ]);

        $decoder = new TokenDecoder();

        $this->assertFalse($decoder->supports($response));
        $this->assertSame('application/json', $decoder->getContentType());
    }

    /**
     * @test
     */
    public function decode_response_into_token()
    {
        $decoder = new TokenDecoder();
        $token = $decoder->unserialize($this->response());

        $this->assertSame('b6d8bd06-a1e1-4e81-9a1b-e230b17a3cb8', $token->getAccessToken());
        $this->assertSame('a47f3aa1-d6d7-4d93-ab21-10fbc13250b7', $token->getRefreshToken());
        $this->assertFalse($token->isExpired());
        $this->assertNotNull($token->getExpiration());
        $this->assertSame('bearer', $token->getType());
        $this->assertEquals(['foo','bar'], $token->getScope());
    }
}
