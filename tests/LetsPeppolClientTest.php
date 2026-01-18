<?php

namespace LetsPeppolSdk\Tests;

use LetsPeppolSdk\LetsPeppolClient;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for LetsPeppolClient class
 */
class LetsPeppolClientTest extends TestCase
{
    private string $tempLogFile;

    protected function setUp(): void
    {
        $this->tempLogFile = sys_get_temp_dir() . '/test-client-' . uniqid() . '.log';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempLogFile)) {
            unlink($this->tempLogFile);
        }
    }

    /**
     * @test
     */
    public function it_can_be_instantiated_with_defaults(): void
    {
        // Act
        $client = new LetsPeppolClient();

        // Assert
        $this->assertInstanceOf(LetsPeppolClient::class, $client);
    }

    /**
     * @test
     */
    public function it_can_be_instantiated_with_custom_urls(): void
    {
        // Act
        $client = new LetsPeppolClient(
            kycUrl: 'https://kyc.test.com',
            proxyUrl: 'https://proxy.test.com',
            appUrl: 'https://app.test.com'
        );

        // Assert
        $this->assertInstanceOf(LetsPeppolClient::class, $client);
    }

    /**
     * @test
     */
    public function it_can_be_instantiated_with_token(): void
    {
        // Act
        $client = new LetsPeppolClient(
            token: 'test-token'
        );

        // Assert
        $this->assertInstanceOf(LetsPeppolClient::class, $client);
        $this->assertSame('test-token', $client->getToken());
    }

    /**
     * @test
     */
    public function it_can_be_instantiated_with_log_file(): void
    {
        // Act
        $client = new LetsPeppolClient(
            logFile: $this->tempLogFile
        );

        // Assert
        $this->assertInstanceOf(LetsPeppolClient::class, $client);
    }

    /**
     * @test
     */
    public function it_provides_kyc_client(): void
    {
        // Arrange
        $client = new LetsPeppolClient();

        // Act
        $kycClient = $client->kyc();

        // Assert
        $this->assertInstanceOf(\LetsPeppolSdk\Resources\KycClient::class, $kycClient);
    }

    /**
     * @test
     */
    public function it_provides_proxy_client(): void
    {
        // Arrange
        $client = new LetsPeppolClient();

        // Act
        $proxyClient = $client->proxy();

        // Assert
        $this->assertInstanceOf(\LetsPeppolSdk\Resources\ProxyClient::class, $proxyClient);
    }

    /**
     * @test
     */
    public function it_provides_app_client(): void
    {
        // Arrange
        $client = new LetsPeppolClient();

        // Act
        $appClient = $client->app();

        // Assert
        $this->assertInstanceOf(\LetsPeppolSdk\Resources\AppClient::class, $appClient);
    }

    /**
     * @test
     */
    public function it_returns_null_when_token_not_set(): void
    {
        // Arrange
        $client = new LetsPeppolClient();

        // Act & Assert
        $this->assertNull($client->getToken());
    }

    /**
     * @test
     */
    public function it_returns_token_when_set(): void
    {
        // Arrange
        $client = new LetsPeppolClient(token: 'test-token');

        // Act & Assert
        $this->assertSame('test-token', $client->getToken());
    }

    /**
     * @test
     */
    public function it_updates_token_when_set(): void
    {
        // Arrange
        $client = new LetsPeppolClient();
        $this->assertNull($client->getToken());

        // Act
        $client->setToken('new-token');

        // Assert
        $this->assertSame('new-token', $client->getToken());
    }

    /**
     * @test
     */
    public function it_returns_itself_when_setting_token(): void
    {
        // Arrange
        $client = new LetsPeppolClient();

        // Act
        $result = $client->setToken('new-token');

        // Assert
        $this->assertSame($client, $result);
    }

    /**
     * @test
     */
    public function it_can_be_created_with_token_factory_method(): void
    {
        // Act
        $client = LetsPeppolClient::withToken('factory-token');

        // Assert
        $this->assertInstanceOf(LetsPeppolClient::class, $client);
        $this->assertSame('factory-token', $client->getToken());
    }

    /**
     * @test
     */
    public function it_supports_custom_urls_in_factory_method(): void
    {
        // Act
        $client = LetsPeppolClient::withToken(
            token: 'factory-token',
            kycUrl: 'https://kyc.test.com',
            proxyUrl: 'https://proxy.test.com',
            appUrl: 'https://app.test.com'
        );

        // Assert
        $this->assertInstanceOf(LetsPeppolClient::class, $client);
        $this->assertSame('factory-token', $client->getToken());
    }

    /**
     * @test
     */
    public function it_supports_log_file_in_factory_method(): void
    {
        // Act
        $client = LetsPeppolClient::withToken(
            token: 'factory-token',
            logFile: $this->tempLogFile
        );

        // Assert
        $this->assertInstanceOf(LetsPeppolClient::class, $client);
        $this->assertSame('factory-token', $client->getToken());
    }

    /**
     * @test
     */
    public function it_updates_all_sessions_when_token_is_set(): void
    {
        // Arrange
        $client = new LetsPeppolClient();

        // Act
        $client->setToken('unified-token');

        // Assert
        $this->assertSame('unified-token', $client->getToken());
    }

    /**
     * @test
     */
    public function it_works_with_all_parameters(): void
    {
        // Act
        $client = new LetsPeppolClient(
            kycUrl: 'https://kyc.test.com',
            proxyUrl: 'https://proxy.test.com',
            appUrl: 'https://app.test.com',
            token: 'test-token',
            logFile: $this->tempLogFile
        );

        // Assert
        $this->assertInstanceOf(LetsPeppolClient::class, $client);
        $this->assertSame('test-token', $client->getToken());
    }

    /**
     * @test
     */
    public function it_returns_correct_client_types(): void
    {
        // Arrange
        $client = new LetsPeppolClient();

        // Act & Assert
        $this->assertInstanceOf(\LetsPeppolSdk\Resources\KycClient::class, $client->kyc());
        $this->assertInstanceOf(\LetsPeppolSdk\Resources\ProxyClient::class, $client->proxy());
        $this->assertInstanceOf(\LetsPeppolSdk\Resources\AppClient::class, $client->app());
    }

    /**
     * @test
     */
    public function it_returns_same_kyc_client_instance(): void
    {
        // Arrange
        $client = new LetsPeppolClient();

        // Act
        $kyc1 = $client->kyc();
        $kyc2 = $client->kyc();

        // Assert
        $this->assertSame($kyc1, $kyc2);
    }

    /**
     * @test
     */
    public function it_returns_same_proxy_client_instance(): void
    {
        // Arrange
        $client = new LetsPeppolClient();

        // Act
        $proxy1 = $client->proxy();
        $proxy2 = $client->proxy();

        // Assert
        $this->assertSame($proxy1, $proxy2);
    }

    /**
     * @test
     */
    public function it_returns_same_app_client_instance(): void
    {
        // Arrange
        $client = new LetsPeppolClient();

        // Act
        $app1 = $client->app();
        $app2 = $client->app();

        // Assert
        $this->assertSame($app1, $app2);
    }
}
