<?php

namespace LetsPeppolSdk\Tests\Unit;

use LetsPeppolSdk\LetsPeppolClient;
use LetsPeppolSdk\Resources\KycClient;
use LetsPeppolSdk\Exceptions\AuthenticationException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit test for authentication functionality
 *
 * Tests the authentication method based on the authenticateExample() from problem statement
 */
#[CoversClass(LetsPeppolClient::class)]
#[CoversClass(KycClient::class)]
class AuthenticationTest extends TestCase
{
    #[Test]
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
    #[Test]
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
    #[Test]
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
}
