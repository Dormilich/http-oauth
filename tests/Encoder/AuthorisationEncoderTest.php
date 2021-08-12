<?php

namespace Tests\Encoder;

use Dormilich\HttpClient\Exception\RequestException;
use Dormilich\HttpOauth\Encoder\AuthorisationEncoder;
use Dormilich\HttpOauth\Exception\CredentialsNotFoundException;
use Dormilich\HttpOauth\Token;
use Dormilich\HttpOauth\TokenProviderInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * @covers \Dormilich\HttpOauth\Encoder\AuthorisationEncoder
 * @uses \Dormilich\HttpOauth\Token
 */
class AuthorisationEncoderTest extends TestCase
{
    /**
     * @test
     */
    public function ignore_non_requests()
    {
        $provider = $this->createStub(TokenProviderInterface::class);
        $decoder = new AuthorisationEncoder($provider);

        $this->assertFalse($decoder->supports(new \stdClass()));
    }

    /**
     * @test
     */
    public function ignore_already_authorised_requests()
    {
        $request = $this->createStub(RequestInterface::class);
        $request
            ->method('hasHeader')
            ->willReturnMap([['Authorization', true]]);

        $provider = $this->createStub(TokenProviderInterface::class);
        $decoder = new AuthorisationEncoder($provider);

        $this->assertFalse($decoder->supports($request));
    }

    /**
     * @test
     */
    public function supports_unauthorised_requests()
    {
        $request = $this->createStub(RequestInterface::class);
        $request
            ->method('hasHeader')
            ->willReturnMap([['Authorization', false]]);

        $provider = $this->createStub(TokenProviderInterface::class);
        $decoder = new AuthorisationEncoder($provider);

        $this->assertTrue($decoder->supports($request));
        $this->assertNull($decoder->getContentType());
    }

    /**
     * @test
     */
    public function add_authorisation_header()
    {
        $token = new Token('b6d8bd06-a1e1-4e81-9a1b-e230b17a3cb8', 'bearer');

        $uri = $this->createStub(UriInterface::class);
        $request = $this->createMock(RequestInterface::class);
        $request
            ->method('getUri')
            ->willReturn($uri);
        $request
            ->expects($this->once())
            ->method('withHeader')
            ->with(
                $this->identicalTo('Authorization'),
                $this->identicalTo('Bearer b6d8bd06-a1e1-4e81-9a1b-e230b17a3cb8')
            )
            ->willReturnSelf();

        $provider = $this->createMock(TokenProviderInterface::class);
        $provider
            ->expects($this->once())
            ->method('getToken')
            ->with($this->identicalTo($uri))
            ->willReturn($token);

        $decoder = new AuthorisationEncoder($provider);
        $decoder->serialize($request, null);
    }

    /**
     * @test
     */
    public function oauth_request_fails_without_credentials()
    {
        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('OAuth credentials missing');

        $uri = $this->createStub(UriInterface::class);
        $request = $this->createMock(RequestInterface::class);
        $request
            ->method('getUri')
            ->willReturn($uri);
        $request
            ->expects($this->never())
            ->method('withHeader');

        $provider = $this->createMock(TokenProviderInterface::class);
        $provider
            ->expects($this->once())
            ->method('getToken')
            ->willThrowException(new CredentialsNotFoundException('OAuth credentials missing'));

        $decoder = new AuthorisationEncoder($provider);
        $decoder->serialize($request, null);
    }

    /**
     * @test
     */
    public function oauth_request_failure()
    {
        $this->expectException(RequestException::class);

        $uri = $this->createStub(UriInterface::class);
        $request = $this->createMock(RequestInterface::class);
        $request
            ->method('getUri')
            ->willReturn($uri);
        $request
            ->expects($this->never())
            ->method('withHeader');

        $oauth = $this->createStub(RequestInterface::class);
        $error = new RequestException('connection timeout');
        $error->setRequest($oauth);
        $provider = $this->createMock(TokenProviderInterface::class);
        $provider
            ->expects($this->once())
            ->method('getToken')
            ->willThrowException($error);

        try {
            $decoder = new AuthorisationEncoder($provider);
            $decoder->serialize($request, null);
        } catch (RequestException $e) {
            $this->assertNotSame($request, $e->getRequest());
            throw $e;
        }
    }
}
