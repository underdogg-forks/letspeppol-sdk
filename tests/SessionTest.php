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

    /**
     * @test
     */
    public function it_can_be_instantiated_with_minimal_parameters(): void
    {
        // Act
        $session = new Session('https://api.example.com');

        // Assert
        $this->assertInstanceOf(Session::class, $session);
    }

    /**
     * @test
     */
    public function it_can_be_instantiated_with_token(): void
    {
        // Act
        $session = new Session('https://api.example.com', 'test-token');

        // Assert
        $this->assertInstanceOf(Session::class, $session);
        $this->assertSame('test-token', $session->getToken());
    }

    /**
     * @test
     */
    public function it_can_be_instantiated_with_log_file(): void
    {
        // Act
        $session = new Session('https://api.example.com', null, [], $this->tempLogFile);

        // Assert
        $this->assertInstanceOf(Session::class, $session);
        $this->assertSame($this->tempLogFile, $session->getLogFile());
    }

    /**
     * @test
     */
    public function it_returns_guzzle_client_instance(): void
    {
        // Arrange
        $session = new Session('https://api.example.com');

        // Act
        $client = $session->getClient();

        // Assert
        $this->assertInstanceOf(\GuzzleHttp\Client::class, $client);
        $this->assertInstanceOf(\LetsPeppolSdk\GuzzleClient::class, $client);
    }

    /**
     * @test
     */
    public function it_returns_base_url(): void
    {
        // Arrange
        $session = new Session('https://api.example.com');

        // Act & Assert
        $this->assertSame('https://api.example.com', $session->getBaseUrl());
    }

    /**
     * @test
     */
    public function it_trims_trailing_slash_from_base_url(): void
    {
        // Arrange
        $session = new Session('https://api.example.com/');

        // Act & Assert
        $this->assertSame('https://api.example.com', $session->getBaseUrl());
    }

    /**
     * @test
     */
    public function it_returns_null_when_token_not_set(): void
    {
        // Arrange
        $session = new Session('https://api.example.com');

        // Act & Assert
        $this->assertNull($session->getToken());
    }

    /**
     * @test
     */
    public function it_returns_token_when_set(): void
    {
        // Arrange
        $session = new Session('https://api.example.com', 'my-token');

        // Act & Assert
        $this->assertSame('my-token', $session->getToken());
    }

    /**
     * @test
     */
    public function it_updates_token_when_set(): void
    {
        // Arrange
        $session = new Session('https://api.example.com');
        $this->assertNull($session->getToken());

        // Act
        $session->setToken('new-token');

        // Assert
        $this->assertSame('new-token', $session->getToken());
    }

    /**
     * @test
     */
    public function it_returns_null_when_log_file_not_set(): void
    {
        // Arrange
        $session = new Session('https://api.example.com');

        // Act & Assert
        $this->assertNull($session->getLogFile());
    }

    /**
     * @test
     */
    public function it_returns_log_file_when_set(): void
    {
        // Arrange
        $session = new Session('https://api.example.com', null, [], $this->tempLogFile);

        // Act & Assert
        $this->assertSame($this->tempLogFile, $session->getLogFile());
    }

    /**
     * @test
     */
    public function it_updates_log_file_when_set(): void
    {
        // Arrange
        $session = new Session('https://api.example.com');
        $this->assertNull($session->getLogFile());

        // Act
        $session->setLogFile($this->tempLogFile);

        // Assert
        $this->assertSame($this->tempLogFile, $session->getLogFile());
    }

    /**
     * @test
     */
    public function it_allows_log_file_to_be_set_to_null(): void
    {
        // Arrange
        $session = new Session('https://api.example.com', null, [], $this->tempLogFile);
        $this->assertSame($this->tempLogFile, $session->getLogFile());

        // Act
        $session->setLogFile(null);

        // Assert
        $this->assertNull($session->getLogFile());
    }

    /**
     * @test
     */
    public function it_recreates_client_when_token_is_set(): void
    {
        // Arrange
        $session = new Session('https://api.example.com');
        $client1 = $session->getClient();

        // Act
        $session->setToken('new-token');
        $client2 = $session->getClient();

        // Assert
        $this->assertNotSame($client1, $client2);
    }

    /**
     * @test
     */
    public function it_recreates_client_when_log_file_is_set(): void
    {
        // Arrange
        $session = new Session('https://api.example.com');
        $client1 = $session->getClient();

        // Act
        $session->setLogFile($this->tempLogFile);
        $client2 = $session->getClient();

        // Assert
        $this->assertNotSame($client1, $client2);
    }

    /**
     * @test
     */
    public function it_accepts_custom_client_options(): void
    {
        // Arrange
        $options = [
            'timeout' => 60,
            'headers' => [
                'Custom-Header' => 'value',
            ],
        ];

        // Act
        $session = new Session('https://api.example.com', null, $options);

        // Assert
        $this->assertInstanceOf(Session::class, $session);
    }

    /**
     * @test
     */
    public function it_works_with_all_parameters(): void
    {
        // Act
        $session = new Session(
            'https://api.example.com/',
            'test-token',
            ['timeout' => 30],
            $this->tempLogFile
        );

        // Assert
        $this->assertSame('https://api.example.com', $session->getBaseUrl());
        $this->assertSame('test-token', $session->getToken());
        $this->assertSame($this->tempLogFile, $session->getLogFile());
        $this->assertInstanceOf(\GuzzleHttp\Client::class, $session->getClient());
    }
}
