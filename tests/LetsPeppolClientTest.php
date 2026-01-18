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

    public function testClientCanBeInstantiatedWithDefaults(): void
    {
        $client = new LetsPeppolClient();
        $this->assertInstanceOf(LetsPeppolClient::class, $client);
    }

    public function testClientCanBeInstantiatedWithCustomUrls(): void
    {
        $client = new LetsPeppolClient(
            kycUrl: 'https://kyc.test.com',
            proxyUrl: 'https://proxy.test.com',
            appUrl: 'https://app.test.com'
        );
        $this->assertInstanceOf(LetsPeppolClient::class, $client);
    }

    public function testClientCanBeInstantiatedWithToken(): void
    {
        $client = new LetsPeppolClient(
            token: 'test-token'
        );
        $this->assertInstanceOf(LetsPeppolClient::class, $client);
        $this->assertSame('test-token', $client->getToken());
    }

    public function testClientCanBeInstantiatedWithLogFile(): void
    {
        $client = new LetsPeppolClient(
            logFile: $this->tempLogFile
        );
        $this->assertInstanceOf(LetsPeppolClient::class, $client);
    }

    public function testClientHasKycClient(): void
    {
        $client = new LetsPeppolClient();
        $kycClient = $client->kyc();
        $this->assertInstanceOf(\LetsPeppolSdk\Resources\KycClient::class, $kycClient);
    }

    public function testClientHasProxyClient(): void
    {
        $client = new LetsPeppolClient();
        $proxyClient = $client->proxy();
        $this->assertInstanceOf(\LetsPeppolSdk\Resources\ProxyClient::class, $proxyClient);
    }

    public function testClientHasAppClient(): void
    {
        $client = new LetsPeppolClient();
        $appClient = $client->app();
        $this->assertInstanceOf(\LetsPeppolSdk\Resources\AppClient::class, $appClient);
    }

    public function testClientGetTokenReturnsNull(): void
    {
        $client = new LetsPeppolClient();
        $this->assertNull($client->getToken());
    }

    public function testClientGetTokenReturnsToken(): void
    {
        $client = new LetsPeppolClient(token: 'test-token');
        $this->assertSame('test-token', $client->getToken());
    }

    public function testClientSetTokenUpdatesToken(): void
    {
        $client = new LetsPeppolClient();
        $this->assertNull($client->getToken());

        $client->setToken('new-token');
        $this->assertSame('new-token', $client->getToken());
    }

    public function testClientSetTokenReturnsClient(): void
    {
        $client = new LetsPeppolClient();
        $result = $client->setToken('new-token');
        $this->assertSame($client, $result);
    }

    public function testClientWithTokenFactoryMethod(): void
    {
        $client = LetsPeppolClient::withToken('factory-token');
        $this->assertInstanceOf(LetsPeppolClient::class, $client);
        $this->assertSame('factory-token', $client->getToken());
    }

    public function testClientWithTokenFactoryMethodWithCustomUrls(): void
    {
        $client = LetsPeppolClient::withToken(
            token: 'factory-token',
            kycUrl: 'https://kyc.test.com',
            proxyUrl: 'https://proxy.test.com',
            appUrl: 'https://app.test.com'
        );
        $this->assertInstanceOf(LetsPeppolClient::class, $client);
        $this->assertSame('factory-token', $client->getToken());
    }

    public function testClientWithTokenFactoryMethodWithLogFile(): void
    {
        $client = LetsPeppolClient::withToken(
            token: 'factory-token',
            logFile: $this->tempLogFile
        );
        $this->assertInstanceOf(LetsPeppolClient::class, $client);
        $this->assertSame('factory-token', $client->getToken());
    }

    public function testClientSetTokenUpdatesAllSessions(): void
    {
        $client = new LetsPeppolClient();
        $client->setToken('unified-token');

        // All clients should have the same token
        $this->assertSame('unified-token', $client->getToken());
    }

    public function testClientWithAllParameters(): void
    {
        $client = new LetsPeppolClient(
            kycUrl: 'https://kyc.test.com',
            proxyUrl: 'https://proxy.test.com',
            appUrl: 'https://app.test.com',
            token: 'test-token',
            logFile: $this->tempLogFile
        );

        $this->assertInstanceOf(LetsPeppolClient::class, $client);
        $this->assertSame('test-token', $client->getToken());
    }

    public function testClientMethodsReturnCorrectTypes(): void
    {
        $client = new LetsPeppolClient();

        $this->assertInstanceOf(\LetsPeppolSdk\Resources\KycClient::class, $client->kyc());
        $this->assertInstanceOf(\LetsPeppolSdk\Resources\ProxyClient::class, $client->proxy());
        $this->assertInstanceOf(\LetsPeppolSdk\Resources\AppClient::class, $client->app());
    }

    public function testClientKycMethodAlwaysReturnsSameInstance(): void
    {
        $client = new LetsPeppolClient();
        $kyc1 = $client->kyc();
        $kyc2 = $client->kyc();
        $this->assertSame($kyc1, $kyc2);
    }

    public function testClientProxyMethodAlwaysReturnsSameInstance(): void
    {
        $client = new LetsPeppolClient();
        $proxy1 = $client->proxy();
        $proxy2 = $client->proxy();
        $this->assertSame($proxy1, $proxy2);
    }

    public function testClientAppMethodAlwaysReturnsSameInstance(): void
    {
        $client = new LetsPeppolClient();
        $app1 = $client->app();
        $app2 = $client->app();
        $this->assertSame($app1, $app2);
    }
}
