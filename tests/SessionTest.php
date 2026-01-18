<?php

namespace LetsPeppolSdk\Tests;

use LetsPeppolSdk\Session;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for Session class
 */
class SessionTest extends TestCase
{
    private string $tempLogFile;

    protected function setUp(): void
    {
        $this->tempLogFile = sys_get_temp_dir() . '/test-session-' . uniqid() . '.log';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempLogFile)) {
            unlink($this->tempLogFile);
        }
    }

    public function testSessionCanBeInstantiatedWithMinimalParameters(): void
    {
        $session = new Session('https://api.example.com');
        $this->assertInstanceOf(Session::class, $session);
    }

    public function testSessionCanBeInstantiatedWithToken(): void
    {
        $session = new Session('https://api.example.com', 'test-token');
        $this->assertInstanceOf(Session::class, $session);
        $this->assertSame('test-token', $session->getToken());
    }

    public function testSessionCanBeInstantiatedWithLogFile(): void
    {
        $session = new Session('https://api.example.com', null, [], $this->tempLogFile);
        $this->assertInstanceOf(Session::class, $session);
        $this->assertSame($this->tempLogFile, $session->getLogFile());
    }

    public function testSessionGetClientReturnsClient(): void
    {
        $session = new Session('https://api.example.com');
        $client = $session->getClient();
        $this->assertInstanceOf(\GuzzleHttp\Client::class, $client);
    }

    public function testSessionGetBaseUrlReturnsUrl(): void
    {
        $session = new Session('https://api.example.com');
        $this->assertSame('https://api.example.com', $session->getBaseUrl());
    }

    public function testSessionTrimsTrailingSlashFromBaseUrl(): void
    {
        $session = new Session('https://api.example.com/');
        $this->assertSame('https://api.example.com', $session->getBaseUrl());
    }

    public function testSessionGetTokenReturnsNull(): void
    {
        $session = new Session('https://api.example.com');
        $this->assertNull($session->getToken());
    }

    public function testSessionGetTokenReturnsToken(): void
    {
        $session = new Session('https://api.example.com', 'my-token');
        $this->assertSame('my-token', $session->getToken());
    }

    public function testSessionSetTokenUpdatesToken(): void
    {
        $session = new Session('https://api.example.com');
        $this->assertNull($session->getToken());

        $session->setToken('new-token');
        $this->assertSame('new-token', $session->getToken());
    }

    public function testSessionGetLogFileReturnsNull(): void
    {
        $session = new Session('https://api.example.com');
        $this->assertNull($session->getLogFile());
    }

    public function testSessionGetLogFileReturnsLogFile(): void
    {
        $session = new Session('https://api.example.com', null, [], $this->tempLogFile);
        $this->assertSame($this->tempLogFile, $session->getLogFile());
    }

    public function testSessionSetLogFileUpdatesLogFile(): void
    {
        $session = new Session('https://api.example.com');
        $this->assertNull($session->getLogFile());

        $session->setLogFile($this->tempLogFile);
        $this->assertSame($this->tempLogFile, $session->getLogFile());
    }

    public function testSessionSetLogFileCanBeSetToNull(): void
    {
        $session = new Session('https://api.example.com', null, [], $this->tempLogFile);
        $this->assertSame($this->tempLogFile, $session->getLogFile());

        $session->setLogFile(null);
        $this->assertNull($session->getLogFile());
    }

    public function testSessionRecreatesClientWhenTokenIsSet(): void
    {
        $session = new Session('https://api.example.com');
        $client1 = $session->getClient();

        $session->setToken('new-token');
        $client2 = $session->getClient();

        // Client should be recreated (different instance)
        $this->assertNotSame($client1, $client2);
    }

    public function testSessionRecreatesClientWhenLogFileIsSet(): void
    {
        $session = new Session('https://api.example.com');
        $client1 = $session->getClient();

        $session->setLogFile($this->tempLogFile);
        $client2 = $session->getClient();

        // Client should be recreated (different instance)
        $this->assertNotSame($client1, $client2);
    }

    public function testSessionAcceptsCustomClientOptions(): void
    {
        $options = [
            'timeout' => 60,
            'headers' => [
                'Custom-Header' => 'value',
            ],
        ];

        $session = new Session('https://api.example.com', null, $options);
        $this->assertInstanceOf(Session::class, $session);
    }

    public function testSessionWithAllParameters(): void
    {
        $session = new Session(
            'https://api.example.com/',
            'test-token',
            ['timeout' => 30],
            $this->tempLogFile
        );

        $this->assertSame('https://api.example.com', $session->getBaseUrl());
        $this->assertSame('test-token', $session->getToken());
        $this->assertSame($this->tempLogFile, $session->getLogFile());
        $this->assertInstanceOf(\GuzzleHttp\Client::class, $session->getClient());
    }
}
