<?php

namespace LetsPeppolSdk\Tests;

use LetsPeppolSdk\Config;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for Config class
 */
class ConfigTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset config to default state before each test
        Config::$endpoint = '';
        Config::$key = '';
        Config::$log_file = '';
    }

    /**
     * @test
     */
    /**
     * @test
     */
    public function it_has_default_values(): void
    {
        // Assert
        $this->assertSame('', Config::$endpoint);
        $this->assertSame('', Config::$key);
        $this->assertSame('', Config::$log_file);
    }

    /**
     * @test
     */
    /**
     * @test
     */
    public function it_allows_setting_endpoint(): void
    {
        // Act
        Config::$endpoint = 'https://api.example.com';

        // Assert
        $this->assertSame('https://api.example.com', Config::$endpoint);
    }

    /**
     * @test
     */
    /**
     * @test
     */
    public function it_allows_setting_key(): void
    {
        // Act
        Config::$key = 'test-api-key-12345';

        // Assert
        $this->assertSame('test-api-key-12345', Config::$key);
    }

    /**
     * @test
     */
    /**
     * @test
     */
    public function it_allows_setting_log_file(): void
    {
        // Act
        Config::$log_file = '/var/log/test.log';

        // Assert
        $this->assertSame('/var/log/test.log', Config::$log_file);
    }

    /**
     * @test
     */
    /**
     * @test
     */
    public function it_maintains_independent_values(): void
    {
        // Arrange & Act
        Config::$endpoint = 'https://api.example.com';
        Config::$key = 'my-key';
        Config::$log_file = '/tmp/test.log';

        // Assert
        $this->assertSame('https://api.example.com', Config::$endpoint);
        $this->assertSame('my-key', Config::$key);
        $this->assertSame('/tmp/test.log', Config::$log_file);
    }

    /**
     * @test
     */
    /**
     * @test
     */
    public function it_can_be_reset(): void
    {
        // Arrange
        Config::$endpoint = 'https://api.example.com';
        Config::$key = 'my-key';
        Config::$log_file = '/tmp/test.log';

        // Act
        Config::$endpoint = '';
        Config::$key = '';
        Config::$log_file = '';

        // Assert
        $this->assertSame('', Config::$endpoint);
        $this->assertSame('', Config::$key);
        $this->assertSame('', Config::$log_file);
    }
}
