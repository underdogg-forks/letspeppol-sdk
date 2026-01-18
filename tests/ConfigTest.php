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

    public function testConfigHasDefaultValues(): void
    {
        $this->assertSame('', Config::$endpoint);
        $this->assertSame('', Config::$key);
        $this->assertSame('', Config::$log_file);
    }

    public function testConfigEndpointCanBeSet(): void
    {
        Config::$endpoint = 'https://api.example.com';
        $this->assertSame('https://api.example.com', Config::$endpoint);
    }

    public function testConfigKeyCanBeSet(): void
    {
        Config::$key = 'test-api-key-12345';
        $this->assertSame('test-api-key-12345', Config::$key);
    }

    public function testConfigLogFileCanBeSet(): void
    {
        Config::$log_file = '/var/log/test.log';
        $this->assertSame('/var/log/test.log', Config::$log_file);
    }

    public function testConfigValuesAreIndependent(): void
    {
        Config::$endpoint = 'https://api.example.com';
        Config::$key = 'my-key';
        Config::$log_file = '/tmp/test.log';

        $this->assertSame('https://api.example.com', Config::$endpoint);
        $this->assertSame('my-key', Config::$key);
        $this->assertSame('/tmp/test.log', Config::$log_file);
    }

    public function testConfigCanBeReset(): void
    {
        Config::$endpoint = 'https://api.example.com';
        Config::$key = 'my-key';
        Config::$log_file = '/tmp/test.log';

        Config::$endpoint = '';
        Config::$key = '';
        Config::$log_file = '';

        $this->assertSame('', Config::$endpoint);
        $this->assertSame('', Config::$key);
        $this->assertSame('', Config::$log_file);
    }
}
