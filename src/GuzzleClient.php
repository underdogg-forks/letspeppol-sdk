<?php

namespace LetsPeppolSdk;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use GuzzleHttp\RequestOptions;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;

/**
 * Custom Guzzle HTTP client with logging support
 * 
 * Extends GuzzleHttp\Client to add:
 * - Request/response logging via Monolog
 * - Custom error handling for 401 and 500 status codes
 */
class GuzzleClient extends \GuzzleHttp\Client
{
    private ?Logger $logger = null;

    /**
     * Create a new GuzzleClient instance
     *
     * @param array $config Guzzle client configuration
     * @param string|null $logFile Path to log file. If provided, enables request/response logging
     */
    public function __construct(array $config = [], ?string $logFile = null)
    {
        // If log file is configured, add logging handler stack
        if (!empty($logFile)) {
            $config['handler'] = $this->createLoggingHandlerStack([
                '{method} {uri} HTTP/{version} {req_body} - {req_headers}',
                "RESPONSE: {code} - {res_body}\n",
            ], $logFile);
        }

        // Ensure http_errors is disabled so we can handle errors manually
        $config['http_errors'] = false;

        parent::__construct($config);
    }

    /**
     * Override request method to add custom error handling
     *
     * @param string $method HTTP method
     * @param string $uri URI
     * @param array $options Request options
     * @return ResponseInterface
     * @throws \Exception When authentication fails (401) or server error occurs (500)
     */
    public function request(string $method, $uri = '', array $options = []): ResponseInterface
    {
        // Ensure synchronous execution
        $options[RequestOptions::SYNCHRONOUS] = true;
        $response = $this->requestAsync($method, $uri, $options)->wait();

        // Handle authentication failure
        if ($response->getStatusCode() === 401) {
            throw new \Exception('Authentication failure');
        }

        // Handle internal server error
        if ($response->getStatusCode() === 500) {
            print_r((string) $response->getBody());
            throw new \Exception('Internal server error');
        }

        return $response;
    }

    /**
     * Create a handler stack with logging middleware
     *
     * @param array $messageFormats Array of message format strings for logging
     * @param string $logFile Path to log file
     * @return HandlerStack Handler stack with logging middleware
     */
    private function createLoggingHandlerStack(array $messageFormats, string $logFile): HandlerStack
    {
        $stack = HandlerStack::create();
        
        foreach ($messageFormats as $messageFormat) {
            $stack->unshift(
                $this->getLogger($messageFormat, $logFile)
            );
        }

        return $stack;
    }

    /**
     * Get logger middleware for the specified message format
     *
     * @param string $messageFormat Message format string for Guzzle MessageFormatter
     * @param string $logFile Path to log file
     * @return callable Logger middleware
     */
    private function getLogger(string $messageFormat, string $logFile): callable
    {
        if (empty($this->logger)) {
            $this->logger = new Logger('letspeppol-sdk-php');
            $formatter = new LineFormatter(null, null, true, true);
            $handler = new StreamHandler($logFile);
            $handler->setFormatter($formatter);
            $this->logger->pushHandler($handler);
        }

        return Middleware::log(
            $this->logger,
            new MessageFormatter($messageFormat)
        );
    }
}
