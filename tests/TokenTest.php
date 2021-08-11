<?php

namespace Tests;

use Dormilich\HttpOauth\Token;
use Dormilich\HttpOauth\TokenInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Dormilich\HttpOauth\Token
 */
class TokenTest extends TestCase
{
    /**
     * @test
     */
    public function token_implements_interface()
    {
        $token = new Token(uniqid(), 'bearer');

        $this->assertInstanceOf(TokenInterface::class, $token);
    }

    /**
     * @test
     */
    public function get_access_token_string()
    {
        $access = uniqid();
        $token = new Token($access, 'bearer');

        $this->assertSame($access, $token->getAccessToken());
        $this->assertSame($access, (string) $token);
    }

    /**
     * @test
     */
    public function get_refresh_token_string()
    {
        $token = new Token(uniqid(), 'bearer');

        $this->assertNull($token->getRefreshToken());

        $refresh = uniqid();
        $token->setRefreshToken($refresh);

        $this->assertSame($refresh, $token->getRefreshToken());
    }

    /**
     * @test
     */
    public function get_token_type()
    {
        $token = new Token(uniqid(), 'bearer');

        $this->assertSame('bearer', $token->getType());
    }

    /**
     * @test
     */
    public function get_token_scope()
    {
        $token = new Token(uniqid(), 'bearer');
        $token->setScope('foo bar');

        $this->assertTrue($token->hasScope('foo'));
        $this->assertTrue($token->hasScope('bar'));
        $this->assertFalse($token->hasScope('baz'));
        $this->assertEquals(['foo', 'bar'], $token->getScope());
    }

    /**
     * @test
     */
    public function token_without_expiration_is_considered_valid()
    {
        $token = new Token(uniqid(), 'bearer');

        $this->assertFalse($token->isExpired());
        $this->assertNull($token->getExpiration());
    }

    /**
     * @test
     * @testWith [100, false]
     *           [-100, true]
     */
    public function get_token_expiration(int $expiresIn, bool $isExpired)
    {
        $token = new Token(uniqid(), 'bearer');

        $time = time() + $expiresIn;
        $token->setExpiration($time);

        $this->assertSame($isExpired, $token->isExpired());
        $this->assertNotNull($token->getExpiration());
        $this->assertEquals($time, $token->getExpiration()->getTimestamp());
    }
}
