<?php

namespace LetsPeppolSdk\Tests;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use LetsPeppolSdk\GuzzleClient;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for GuzzleClient class
 */
class GuzzleClientTest extends TestCase
{
    private string $tempLogFile;

    protected function setUp(): void
    {
        $this->tempLogFile = sys_get_temp_dir() . '/test-guzzle-' . uniqid() . '.log';
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
    public function it_can_be_instantiated_without_logging(): void
    {
        // Act
        $client = new GuzzleClient();

        // Assert
        $this->assertInstanceOf(GuzzleClient::class, $client);
        $this->assertInstanceOf(\GuzzleHttp\Client::class, $client);
    }

    /**
     * @test
     */
    public function it_can_be_instantiated_with_logging(): void
    {
        // Act
        $client = new GuzzleClient([], $this->tempLogFile);

        // Assert
        $this->assertInstanceOf(GuzzleClient::class, $client);
    }

    /**
     * @test
     */
    public function it_extends_guzzle_http_client(): void
    {
        // Act
        $client = new GuzzleClient();

        // Assert
        $this->assertInstanceOf(\GuzzleHttp\Client::class, $client);
    }

    /**
     * @test
     */
    public function it_disables_http_errors_by_default(): void
    {
        // Arrange
        $mock = new MockHandler([
            new Response(404, [], 'Not Found'),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new GuzzleClient(['handler' => $handlerStack]);

        // Act
        $response = $client->request('GET', '/test');

        // Assert
        $this->assertSame(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_throws_exception_on_401_response(): void
    {
        // Arrange
        $mock = new MockHandler([
            new Response(401, [], 'Unauthorized'),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new GuzzleClient(['handler' => $handlerStack]);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Authentication failure');

        // Act
        $client->request('GET', '/test');
    }

    /**
     * @test
     */
    public function it_throws_exception_on_500_response(): void
    {
        // Arrange
        $mock = new MockHandler([
            new Response(500, [], 'Internal Server Error'),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new GuzzleClient(['handler' => $handlerStack]);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Internal server error');

        // Act
        $client->request('GET', '/test');
    }

    /**
     * @test
     */
    public function it_returns_response_on_200_status(): void
    {
        // Arrange
        $mock = new MockHandler([
            new Response(200, [], '{"success": true}'),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new GuzzleClient(['handler' => $handlerStack]);

        // Act
        $response = $client->request('GET', '/test');

        // Assert
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{"success": true}', (string) $response->getBody());
    }

    /**
     * @test
     */
    public function it_returns_response_on_201_status(): void
    {
        // Arrange
        $mock = new MockHandler([
            new Response(201, [], '{"created": true}'),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new GuzzleClient(['handler' => $handlerStack]);

        // Act
        $response = $client->request('POST', '/test');

        // Assert
        $this->assertSame(201, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_handles_other_error_codes_without_exception(): void
    {
        // Arrange
        $statusCodes = [400, 403, 404, 422, 502, 503];

        foreach ($statusCodes as $statusCode) {
            $mock = new MockHandler([
                new Response($statusCode, [], 'Error'),
            ]);
            $handlerStack = HandlerStack::create($mock);
            $client = new GuzzleClient(['handler' => $handlerStack]);

            // Act
            $response = $client->request('GET', '/test');

            // Assert
            $this->assertSame($statusCode, $response->getStatusCode());
        }
    }

    /**
     * @test
     */
    public function it_can_be_instantiated_with_log_file_parameter(): void
    {
        // Act
        $client = new GuzzleClient([
            'base_uri' => 'https://httpbin.org',
        ], $this->tempLogFile);

        // Assert
        $this->assertInstanceOf(GuzzleClient::class, $client);
    }

    /**
     * @test
     */
    public function it_does_not_create_log_file_when_logging_disabled(): void
    {
        // Arrange
        $mock = new MockHandler([
            new Response(200, [], '{"test": true}'),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new GuzzleClient(['handler' => $handlerStack]);

        // Act
        $response = $client->request('GET', '/test');

        // Assert
        $this->assertSame(200, $response->getStatusCode());
        $this->assertFileDoesNotExist($this->tempLogFile);
    }

    /**
     * @test
     */
    public function it_accepts_custom_configuration(): void
    {
        // Arrange
        $config = [
            'base_uri' => 'https://api.example.com',
            'timeout' => 60,
            'headers' => [
                'User-Agent' => 'Test Client',
            ],
        ];

        // Act
        $client = new GuzzleClient($config);

        // Assert
        $this->assertInstanceOf(GuzzleClient::class, $client);
    }

    /**
     * @test
     */
    public function it_handles_500_error_with_logging(): void
    {
        // Arrange
        $mock = new MockHandler([
            new Response(500, [], 'Server error details'),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new GuzzleClient([
            'handler' => $handlerStack,
        ]);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Internal server error');

        // Act
        $client->request('GET', '/test');
    }

    /**
     * @test
     */
    public function it_has_callable_request_method(): void
    {
        // Arrange
        $client = new GuzzleClient();

        // Assert
        $this->assertTrue(method_exists($client, 'request'));
    }
}
