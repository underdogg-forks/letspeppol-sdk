<?php

namespace LetsPeppolSdk;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use GuzzleHttp\RequestOptions;
use LetsPeppolSdk\Exceptions\AuthenticationException;
use LetsPeppolSdk\Exceptions\ServerErrorException;
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
     * @param string|null $logFile Path to log file. If provided, enables request/response logging.
     *                              Falls back to Config::$logFile if not provided.
     * @throws \RuntimeException If log file directory cannot be created or is not writable
     */
    public function __construct(array $config = [], ?string $logFile = null)
    {
        // Fall back to Config::$logFile if no log file provided
        if ($logFile === null && !empty(Config::$logFile)) {
            $logFile = Config::$logFile;
        }

        // If log file is configured, add logging handler stack
        // WARNING: Logs include request/response bodies and headers which may contain
        // sensitive information (API keys, tokens, passwords, personal data).
        // Ensure log files are secured and comply with data protection regulations.
        if (!empty($logFile)) {
            // Validate log file path early
            $this->validateLogFilePath($logFile);
            
            // Merge logging handler with existing handler if present
            if (isset($config['handler']) && $config['handler'] instanceof HandlerStack) {
                // Add logging middleware to existing handler stack
                $stack = $config['handler'];
                $this->addLoggingMiddleware($stack, $logFile);
            } else {
                // Create new handler stack with logging
                $config['handler'] = $this->createLoggingHandlerStack([
                    '{method} {uri} HTTP/{version} {req_body} - {req_headers}',
                    "RESPONSE: {code} - {res_body}\n",
                ], $logFile);
            }
        }

        // Ensure http_errors is disabled so we can handle errors manually
        $config['http_errors'] = false;

        parent::__construct($config);
    }

    /**
     * Validate that the log file path is writable
     *
     * @param string $logFile Path to log file
     * @throws \RuntimeException If path is not writable
     */
    private function validateLogFilePath(string $logFile): void
    {
        $directory = \dirname($logFile);
        
        // Check if directory exists or can be created
        if ($directory !== '' && !\is_dir($directory)) {
            if (!@mkdir($directory, 0777, true) && !\is_dir($directory)) {
                throw new \RuntimeException(sprintf('Unable to create log directory: %s', $directory));
            }
        }
        
        // Check if directory is writable
        if (!\is_writable($directory)) {
            throw new \RuntimeException(sprintf('Log directory is not writable: %s', $directory));
        }
    }

    /**
     * Override request method to add custom error handling
     *
     * @param string $method HTTP method
     * @param string $uri URI
     * @param array $options Request options
     * @return ResponseInterface
     * @throws AuthenticationException When authentication fails (401)
     * @throws ServerErrorException When server error occurs (500)
     */
    public function request(string $method, $uri = '', array $options = []): ResponseInterface
    {
        // Ensure synchronous execution
        $options[RequestOptions::SYNCHRONOUS] = true;
        $response = $this->requestAsync($method, $uri, $options)->wait();

        // Handle authentication failure
        if ($response->getStatusCode() === 401) {
            throw new AuthenticationException('Authentication failure');
        }

        // Handle internal server error
        if ($response->getStatusCode() === 500) {
            $body = (string) $response->getBody();
            // Log error for debugging
            if ($this->logger !== null) {
                $this->logger->error('Internal server error', ['body' => $body]);
            }
            throw new ServerErrorException('Internal server error: ' . $body);
        }

        return $response;
    }

    /**
     * Add logging middleware to an existing handler stack
     *
     * @param HandlerStack $stack Handler stack to add middleware to
     * @param string $logFile Path to log file
     */
    private function addLoggingMiddleware(HandlerStack $stack, string $logFile): void
    {
        $messageFormats = [
            '{method} {uri} HTTP/{version} {req_body} - {req_headers}',
            "RESPONSE: {code} - {res_body}\n",
        ];
        
        foreach ($messageFormats as $messageFormat) {
            $stack->unshift(
                $this->getLogger($messageFormat, $logFile)
            );
        }
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
        if ($this->logger === null) {
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
