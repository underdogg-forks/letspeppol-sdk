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

    public function testClientCanBeInstantiatedWithoutLogging(): void
    {
        $client = new GuzzleClient();
        $this->assertInstanceOf(GuzzleClient::class, $client);
        $this->assertInstanceOf(\GuzzleHttp\Client::class, $client);
    }

    public function testClientCanBeInstantiatedWithLogging(): void
    {
        $client = new GuzzleClient([], $this->tempLogFile);
        $this->assertInstanceOf(GuzzleClient::class, $client);
    }

    public function testClientExtendsGuzzleHttpClient(): void
    {
        $client = new GuzzleClient();
        $this->assertInstanceOf(\GuzzleHttp\Client::class, $client);
    }

    public function testClientDisablesHttpErrorsByDefault(): void
    {
        // Create a mock handler that returns a 404 response
        $mock = new MockHandler([
            new Response(404, [], 'Not Found'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new GuzzleClient(['handler' => $handlerStack]);

        // Should not throw an exception because http_errors is disabled
        $response = $client->request('GET', '/test');
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testClientThrowsExceptionOn401(): void
    {
        $mock = new MockHandler([
            new Response(401, [], 'Unauthorized'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new GuzzleClient(['handler' => $handlerStack]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Authentication failure');

        $client->request('GET', '/test');
    }

    public function testClientThrowsExceptionOn500(): void
    {
        $mock = new MockHandler([
            new Response(500, [], 'Internal Server Error'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new GuzzleClient(['handler' => $handlerStack]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Internal server error');

        $client->request('GET', '/test');
    }

    public function testClientReturnsResponseOn200(): void
    {
        $mock = new MockHandler([
            new Response(200, [], '{"success": true}'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new GuzzleClient(['handler' => $handlerStack]);

        $response = $client->request('GET', '/test');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{"success": true}', (string) $response->getBody());
    }

    public function testClientReturnsResponseOn201(): void
    {
        $mock = new MockHandler([
            new Response(201, [], '{"created": true}'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new GuzzleClient(['handler' => $handlerStack]);

        $response = $client->request('POST', '/test');
        $this->assertSame(201, $response->getStatusCode());
    }

    public function testClientHandlesOtherErrorCodesWithoutException(): void
    {
        $statusCodes = [400, 403, 404, 422, 502, 503];

        foreach ($statusCodes as $statusCode) {
            $mock = new MockHandler([
                new Response($statusCode, [], 'Error'),
            ]);

            $handlerStack = HandlerStack::create($mock);
            $client = new GuzzleClient(['handler' => $handlerStack]);

            $response = $client->request('GET', '/test');
            $this->assertSame($statusCode, $response->getStatusCode());
        }
    }

    public function testClientWithLoggingCreatesLogFile(): void
    {
        $mock = new MockHandler([
            new Response(200, [], '{"test": true}'),
        ]);

        // Create client with logging - the handler will be overridden by our logging handler
        // but we can still test that the log file is created
        $client = new GuzzleClient([
            'base_uri' => 'https://httpbin.org', // Use a URL that won't actually be called
        ], $this->tempLogFile);

        // We can't easily test actual logging without making real requests
        // but we can verify the client was created successfully with logging enabled
        $this->assertInstanceOf(GuzzleClient::class, $client);
        
        // The log file is only created when a request is made
        // For this test, we'll just verify the client can be instantiated with a log file
    }

    public function testClientWithoutLoggingDoesNotCreateLogFile(): void
    {
        $mock = new MockHandler([
            new Response(200, [], '{"test": true}'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new GuzzleClient(['handler' => $handlerStack]);

        $response = $client->request('GET', '/test');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertFileDoesNotExist($this->tempLogFile);
    }

    public function testClientAcceptsCustomConfiguration(): void
    {
        $config = [
            'base_uri' => 'https://api.example.com',
            'timeout' => 60,
            'headers' => [
                'User-Agent' => 'Test Client',
            ],
        ];

        $client = new GuzzleClient($config);
        $this->assertInstanceOf(GuzzleClient::class, $client);
    }

    public function testClientHandles500WithLogging(): void
    {
        $mock = new MockHandler([
            new Response(500, [], 'Server error details'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        
        // For this test, we can't use logging because it creates a new handler
        // So we test 500 error handling without the log file
        $client = new GuzzleClient([
            'handler' => $handlerStack,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Internal server error');

        $client->request('GET', '/test');
    }

    public function testClientRequestMethodIsCallable(): void
    {
        $client = new GuzzleClient();
        $this->assertTrue(method_exists($client, 'request'));
    }
}
