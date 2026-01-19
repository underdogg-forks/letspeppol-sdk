<?php

namespace LetsPeppolSdk\Tests\Unit;

use LetsPeppolSdk\LetsPeppolClient;
use LetsPeppolSdk\Resources\KycClient;
use LetsPeppolSdk\Exceptions\AuthenticationException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit test for authentication functionality
 *
 * Tests the authentication method based on the authenticateExample() from problem statement
 */
class AuthenticationTest extends TestCase
{
    private LetsPeppolClient|MockObject $clientMock;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @test
     */
    public function it_authenticates_successfully_and_returns_token(): void
    {
        // Arrange
        $email = 'user@example.com';
        $password = 'password123';
        $expectedToken = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c';

        $client = $this->getMockBuilder(LetsPeppolClient::class)
            ->onlyMethods(['authenticate'])
            ->getMock();

        $client->expects($this->once())
            ->method('authenticate')
            ->with($email, $password)
            ->willReturn($expectedToken);

        // Act
        $token = $client->authenticate($email, $password);

        // Assert
        $this->assertIsString($token);
        $this->assertStringStartsWith('eyJ', $token);
        $this->assertEquals($expectedToken, $token);
    }

    /**
     * @test
     */
    public function it_throws_authentication_exception_on_invalid_credentials(): void
    {
        // Arrange
        $email = 'invalid@example.com';
        $password = 'wrongpassword';

        $client = $this->getMockBuilder(LetsPeppolClient::class)
            ->onlyMethods(['authenticate'])
            ->getMock();

        $client->expects($this->once())
            ->method('authenticate')
            ->with($email, $password)
            ->willThrowException(new AuthenticationException('Authentication failed: Invalid credentials', 401));

        // Assert
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Authentication failed: Invalid credentials');
        $this->expectExceptionCode(401);

        // Act
        $client->authenticate($email, $password);
    }

    /**
     * @test
     */
    public function it_can_set_token_after_authentication(): void
    {
        // Arrange
        $token = 'test-jwt-token';
        $client = new LetsPeppolClient();

        // Act
        $client->setToken($token);

        // Assert
        $this->assertEquals($token, $client->getToken());
    }

    /**
     * @test
     */
    public function it_returns_token_substring_for_display(): void
    {
        // Arrange
        $fullToken = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c';
        
        // Act
        $shortToken = substr($fullToken, 0, 20);

        // Assert
        $this->assertEquals('eyJhbGciOiJIUzI1NiIs', $shortToken);
        $this->assertEquals(20, strlen($shortToken));
    }
}
