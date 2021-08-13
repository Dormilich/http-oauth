<?php

namespace Tests;

use Dormilich\HttpClient\Exception\DecoderException;
use Dormilich\HttpClient\Exception\RequestException;
use Dormilich\HttpOauth\Credentials\ClientCredentials;
use Dormilich\HttpOauth\Credentials\CredentialsProviderInterface;
use Dormilich\HttpOauth\TokenClient;
use Dormilich\HttpOauth\TokenInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * @covers \Dormilich\HttpOauth\TokenClient
 * @uses \Dormilich\HttpOauth\Token
 * @uses \Dormilich\HttpOauth\Credentials\ClientCredentials
 * @uses \Dormilich\HttpOauth\Decoder\OauthErrorDecoder
 * @uses \Dormilich\HttpOauth\Decoder\TokenDecoder
 */
class TokenClientTest extends TestCase
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

        $json = json_encode($default);

        return $this->createConfiguredMock(StreamInterface::class, [
            '__toString' => $json,
            'getContents' => $json,
            'getSize' => strlen($json),
        ]);
    }

    private function provider(bool $basic = false)
    {
        $credentials = new ClientCredentials('phpunit', '63618739-4d00-4ff1-88f5-bfeaccd81fc5', 'https://example.com/openid');
        $credentials->setBasicAuth($basic);

        return $this->createConfiguredMock(CredentialsProviderInterface::class, [
            'get' => $credentials,
        ]);
    }

    /**
     * @test
     */
    public function get_client_identifier()
    {
        $uri = $this->createStub(UriInterface::class);
        $http = $this->createStub(ClientInterface::class);
        $rFac = $this->createStub(RequestFactoryInterface::class);
        $sFac = $this->createStub(StreamFactoryInterface::class);
        $client = new TokenClient($this->provider(), $http, $rFac, $sFac);

        $this->assertIsString($client->getCredentialsId($uri));
    }

    /**
     * @test
     */
    public function request_new_token()
    {
        $expData['grant_type'] = 'client_credentials';
        $expData['client_id'] = 'phpunit';
        $expData['client_secret'] = '63618739-4d00-4ff1-88f5-bfeaccd81fc5';

        $http = $this->createConfiguredMock(ClientInterface::class, [
            'sendRequest' => $this->response(),
        ]);

        $stream = $this->createStub(StreamInterface::class);
        $sFac = $this->createMock(StreamFactoryInterface::class);
        $sFac
            ->expects($this->once())
            ->method('createStream')
            ->with($this->identicalTo(http_build_query($expData)))
            ->willReturn($stream);

        $request = $this->createMock(RequestInterface::class);
        $request
            ->expects($this->once())
            ->method('withBody')
            ->with($this->identicalTo($stream))
            ->willReturnSelf();
        $request
            ->expects($this->once())
            ->method('withHeader')
            ->with(
                $this->identicalTo('Content-Type'),
                $this->identicalTo('application/x-www-form-urlencoded')
            )
            ->willReturnSelf();

        $rFac = $this->createMock(RequestFactoryInterface::class);
        $rFac
            ->expects($this->once())
            ->method('createRequest')
            ->with($this->identicalTo('POST'))
            ->willReturn($request);

        $target = $this->createStub(UriInterface::class);

        $client = new TokenClient($this->provider(), $http, $rFac, $sFac);
        $token = $client->requestToken($target);

        $this->assertSame('b6d8bd06-a1e1-4e81-9a1b-e230b17a3cb8', $token->getAccessToken());
        $this->assertSame('a47f3aa1-d6d7-4d93-ab21-10fbc13250b7', $token->getRefreshToken());
        $this->assertFalse($token->isExpired());
        $this->assertSame('bearer', $token->getType());
        $this->assertEquals([], $token->getScope());
    }

    /**
     * @test
     */
    public function request_new_token_with_basic_auth()
    {
        $expData['grant_type'] = 'client_credentials';

        $http = $this->createConfiguredMock(ClientInterface::class, [
            'sendRequest' => $this->response(),
        ]);

        $stream = $this->createStub(StreamInterface::class);
        $sFac = $this->createMock(StreamFactoryInterface::class);
        $sFac
            ->expects($this->once())
            ->method('createStream')
            ->with($this->identicalTo(http_build_query($expData)))
            ->willReturn($stream);

        $request = $this->createMock(RequestInterface::class);
        $request
            ->expects($this->once())
            ->method('withBody')
            ->with($this->identicalTo($stream))
            ->willReturnSelf();
        $request
            ->expects($this->exactly(2))
            ->method('withHeader')
            ->withConsecutive(
                [$this->identicalTo('Authorization'), $this->identicalTo('Basic cGhwdW5pdDo2MzYxODczOS00ZDAwLTRmZjEtODhmNS1iZmVhY2NkODFmYzU=')],
                [$this->identicalTo('Content-Type'), $this->identicalTo('application/x-www-form-urlencoded')]
            )
            ->willReturnSelf();

        $rFac = $this->createMock(RequestFactoryInterface::class);
        $rFac
            ->expects($this->once())
            ->method('createRequest')
            ->with($this->identicalTo('POST'))
            ->willReturn($request);

        $target = $this->createStub(UriInterface::class);

        $client = new TokenClient($this->provider(true), $http, $rFac, $sFac);
        $token = $client->requestToken($target);

        $this->assertSame('b6d8bd06-a1e1-4e81-9a1b-e230b17a3cb8', $token->getAccessToken());
        $this->assertSame('a47f3aa1-d6d7-4d93-ab21-10fbc13250b7', $token->getRefreshToken());
        $this->assertFalse($token->isExpired());
        $this->assertSame('bearer', $token->getType());
        $this->assertEquals([], $token->getScope());
    }

    /**
     * @test
     */
    public function refresh_token()
    {
        $expData['grant_type'] = 'refresh_token';
        $expData['refresh_token'] = 'a47f3aa1-d6d7-4d93-ab21-10fbc13250b7';
        $expData['client_id'] = 'phpunit';
        $expData['client_secret'] = '63618739-4d00-4ff1-88f5-bfeaccd81fc5';

        $http = $this->createConfiguredMock(ClientInterface::class, [
            'sendRequest' => $this->response(),
        ]);

        $stream = $this->createStub(StreamInterface::class);
        $sFac = $this->createMock(StreamFactoryInterface::class);
        $sFac
            ->expects($this->once())
            ->method('createStream')
            ->with($this->identicalTo(http_build_query($expData)))
            ->willReturn($stream);

        $request = $this->createMock(RequestInterface::class);
        $request
            ->expects($this->once())
            ->method('withBody')
            ->with($this->identicalTo($stream))
            ->willReturnSelf();
        $request
            ->expects($this->once())
            ->method('withHeader')
            ->with(
                $this->identicalTo('Content-Type'),
                $this->identicalTo('application/x-www-form-urlencoded')
            )
            ->willReturnSelf();

        $rFac = $this->createMock(RequestFactoryInterface::class);
        $rFac
            ->expects($this->once())
            ->method('createRequest')
            ->with($this->identicalTo('POST'))
            ->willReturn($request);

        $target = $this->createStub(UriInterface::class);
        $token = $this->createConfiguredMock(TokenInterface::class, [
            'getRefreshToken' => 'a47f3aa1-d6d7-4d93-ab21-10fbc13250b7',
        ]);

        $client = new TokenClient($this->provider(), $http, $rFac, $sFac);
        $token = $client->refreshToken($token, $target);

        $this->assertSame('b6d8bd06-a1e1-4e81-9a1b-e230b17a3cb8', $token->getAccessToken());
        $this->assertSame('a47f3aa1-d6d7-4d93-ab21-10fbc13250b7', $token->getRefreshToken());
        $this->assertFalse($token->isExpired());
        $this->assertSame('bearer', $token->getType());
        $this->assertEquals([], $token->getScope());
    }

    /**
     * @test
     * @testWith ["Psr\\Http\\Client\\NetworkExceptionInterface"]
     *           ["Psr\\Http\\Client\\RequestExceptionInterface"]
     */
    public function throw_request_exception_on_http_failure(string $error_class)
    {
        $this->expectException(RequestException::class);

        $request = $this->createStub(RequestInterface::class);
        $request
            ->method('withBody')
            ->willReturnSelf();
        $request
            ->method('withHeader')
            ->willReturnSelf();

        $rFac = $this->createConfiguredMock(RequestFactoryInterface::class, [
            'createRequest' => $request,
        ]);
        $sFac = $this->createConfiguredMock(StreamFactoryInterface::class, [
            'createStream' => $this->createStub(StreamInterface::class),
        ]);

        $error = $this->createConfiguredMock($error_class, [
            'getRequest' => $request,
        ]);
        $http = $this->createStub(ClientInterface::class);
        $http
            ->method('sendRequest')
            ->willThrowException($error);

        $target = $this->createStub(UriInterface::class);

        try {
            $client = new TokenClient($this->provider(), $http, $rFac, $sFac);
            $client->requestToken($target);
        } catch (RequestException $e) {
            $this->assertNotNull($e->getRequest());
            $this->assertNull($e->getResponse());
            throw $e;
        }
    }

    /**
     * @test
     */
    public function throw_request_exception_on_http_client_failure()
    {
        $this->expectException(RequestException::class);

        $request = $this->createStub(RequestInterface::class);
        $request
            ->method('withBody')
            ->willReturnSelf();
        $request
            ->method('withHeader')
            ->willReturnSelf();

        $rFac = $this->createConfiguredMock(RequestFactoryInterface::class, [
            'createRequest' => $request,
        ]);
        $sFac = $this->createConfiguredMock(StreamFactoryInterface::class, [
            'createStream' => $this->createStub(StreamInterface::class),
        ]);
        $http = $this->createStub(ClientInterface::class);
        $http
            ->method('sendRequest')
            ->willThrowException(new HttpError());

        $target = $this->createStub(UriInterface::class);

        try {
            $client = new TokenClient($this->provider(), $http, $rFac, $sFac);
            $client->requestToken($target);
        } catch (RequestException $e) {
            $this->assertNotNull($e->getRequest());
            $this->assertNull($e->getResponse());
            throw $e;
        }
    }

    /**
     * @test
     */
    public function throw_exception_on_invalid_response()
    {
        $this->expectException(DecoderException::class);
        $this->expectExceptionMessage('No decoder was found that could handle the authorisation response.');

        $request = $this->createStub(RequestInterface::class);
        $request
            ->method('withBody')
            ->willReturnSelf();
        $request
            ->method('withHeader')
            ->willReturnSelf();

        $rFac = $this->createConfiguredMock(RequestFactoryInterface::class, [
            'createRequest' => $request,
        ]);
        $sFac = $this->createConfiguredMock(StreamFactoryInterface::class, [
            'createStream' => $this->createStub(StreamInterface::class),
        ]);
        $response = $this->createConfiguredMock(ResponseInterface::class, [
            'getStatusCode' => 500,
        ]);
        $http = $this->createConfiguredMock(ClientInterface::class, [
            'sendRequest' => $response,
        ]);

        $target = $this->createStub(UriInterface::class);

        try {
            $client = new TokenClient($this->provider(), $http, $rFac, $sFac);
            $client->requestToken($target);
        } catch (RequestException $e) {
            $this->assertNotNull($e->getRequest());
            $this->assertNotNull($e->getResponse());
            throw $e;
        }
    }
}

class HttpError extends \Exception implements \Psr\Http\Client\ClientExceptionInterface {}
