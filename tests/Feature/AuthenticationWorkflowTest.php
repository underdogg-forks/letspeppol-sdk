<?php

namespace LetsPeppolSdk\Tests\Feature;

use LetsPeppolSdk\LetsPeppolClient;
use LetsPeppolSdk\Resources\KycClient;
use LetsPeppolSdk\Exceptions\AuthenticationException;
use LetsPeppolSdk\Exceptions\ApiException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Feature test for authentication workflow
 *
 * Tests the complete authentication flow based on authenticateExample()
 */
#[CoversClass(LetsPeppolClient::class)]
#[CoversClass(KycClient::class)]
class AuthenticationWorkflowTest extends TestCase
{
    #[Test]
    public function it_completes_authentication_workflow(): void
    {
        // Arrange
        $email = 'user@example.com';
        $password = 'password123';
        $expectedToken = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.test.token';

        $client = $this->getMockBuilder(LetsPeppolClient::class)
            ->onlyMethods(['authenticate'])
            ->getMock();

        $kycClient = $this->getMockBuilder(KycClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAccountInfo'])
            ->getMock();

        $client->expects($this->once())
            ->method('authenticate')
            ->with($email, $password)
            ->willReturn($expectedToken);

        $kycClient->expects($this->once())
            ->method('getAccountInfo')
            ->willReturn([
                'id' => 1,
                'companyName' => 'Test Company',
                'peppolId' => '0208:BE0123456789',
                'email' => $email
            ]);

        // Act - Simulate the authenticateExample workflow
        try {
            // Step 1: Authenticate and get JWT token
            $token = $client->authenticate($email, $password);
            $this->assertNotEmpty($token);
            
            // Step 2: Display token (first 20 chars)
            $tokenPreview = substr($token, 0, 20);
            $this->assertEquals('eyJhbGciOiJIUzI1NiIs', $tokenPreview);
            
            // Step 3: Get account info
            $account = $kycClient->getAccountInfo();
            $this->assertArrayHasKey('companyName', $account);
            $this->assertEquals('Test Company', $account['companyName']);
            
        } catch (AuthenticationException $e) {
            $this->fail('Authentication should succeed: ' . $e->getMessage());
        } catch (ApiException $e) {
            $this->fail('API call should succeed: ' . $e->getMessage());
        }
    }
    #[Test]
    public function it_handles_authentication_failure(): void
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
            ->willThrowException(new AuthenticationException('Authentication failed', 401));

        // Act
        $authFailed = false;
        try {
            $client->authenticate($email, $password);
        } catch (AuthenticationException $e) {
            $authFailed = true;
            $this->assertEquals('Authentication failed', $e->getMessage());
        }

        // Assert
        $this->assertTrue($authFailed, 'Should catch authentication exception');
    }
    #[Test]
    public function it_handles_api_error_during_account_info_retrieval(): void
    {
        // Arrange
        $email = 'user@example.com';
        $password = 'password123';
        $token = 'valid.jwt.token';

        $client = $this->getMockBuilder(LetsPeppolClient::class)
            ->onlyMethods(['authenticate'])
            ->getMock();

        $kycClient = $this->getMockBuilder(KycClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAccountInfo'])
            ->getMock();

        $client->expects($this->once())
            ->method('authenticate')
            ->willReturn($token);

        $kycClient->expects($this->once())
            ->method('getAccountInfo')
            ->willThrowException(new ApiException('API error', 500));

        // Act
        $apiErrorCaught = false;
        try {
            $client->authenticate($email, $password);
            $kycClient->getAccountInfo();
        } catch (ApiException $e) {
            $apiErrorCaught = true;
            $this->assertEquals('API error', $e->getMessage());
        }

        // Assert
        $this->assertTrue($apiErrorCaught);
    }
    #[Test]
    public function it_sets_and_retrieves_token(): void
    {
        // Arrange
        $client = new LetsPeppolClient();
        $token = 'test.jwt.token';

        // Act
        $client->setToken($token);
        $retrievedToken = $client->getToken();

        // Assert
        $this->assertEquals($token, $retrievedToken);
    }
    #[Test]
    public function it_creates_client_with_token(): void
    {
        // Arrange
        $token = 'factory.jwt.token';

        // Act
        $client = LetsPeppolClient::withToken($token);

        // Assert
        $this->assertInstanceOf(LetsPeppolClient::class, $client);
        $this->assertEquals($token, $client->getToken());
    }
}
