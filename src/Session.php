<?php

namespace LetsPeppolSdk;

use GuzzleHttp\Client;

/**
 * This class manages an HTTP session to the LetsPeppol API.
 */
class Session
{
    private Client $client;
    private string $baseUrl;
    private ?string $token;
    private array $clientOptions;
    private ?string $logFile;

    /**
     * Create a new Session instance configured with optional JWT token and API base URL.
     *
     * @param string $baseUrl Base API URL
     * @param string|null $token Optional JWT token for authenticated requests
     * @param array $clientOptions Optional Guzzle client options to merge with defaults
     * @param string|null $logFile Optional path to log file for request/response logging
     */
    public function __construct(string $baseUrl, ?string $token = null, array $clientOptions = [], ?string $logFile = null)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->token = $token;
        $this->clientOptions = $clientOptions;
        $this->logFile = $logFile;

        $this->client = $this->createClient();
    }

    /**
     * Create a new Guzzle client with current configuration
     *
     * @return Client Configured Guzzle HTTP client
     */
    private function createClient(): Client
    {
        // Set default headers
        $defaults = [
            'base_uri' => $this->baseUrl,
            'headers'  => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
                'User-Agent'   => 'LetsPeppol PHP SDK',
            ],
            'timeout'     => 30,
            'http_errors' => false, // Handle errors manually
        ];

        // Add token if provided
        if ($this->token) {
            $defaults['headers']['Authorization'] = "Bearer {$this->token}";
        }

        // Use custom GuzzleClient with logging support
        return new GuzzleClient(
            array_replace_recursive($defaults, $this->clientOptions),
            $this->logFile,
        );
    }

    /**
     * Retrieve the internal Guzzle HTTP client used to perform API requests.
     *
     * @return Client The configured Guzzle HTTP client.
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * The base API URL used for requests.
     *
     * @return string The base API URL.
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Get the JWT token.
     *
     * @return string|null
     */
    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * Get the log file path.
     *
     * @return string|null
     */
    public function getLogFile(): ?string
    {
        return $this->logFile;
    }

    /**
     * Set the log file path and recreate the client with logging enabled.
     *
     * @param string|null $logFile Path to log file or null to disable logging
     * @return void
     */
    public function setLogFile(?string $logFile): void
    {
        $this->logFile = $logFile;

        // Recreate client with new log file setting
        $this->client = $this->createClient();
    }

    /**
     * Set the JWT token and recreate the client with the new token.
     *
     * @param string $token JWT token
     * @return void
     */
    public function setToken(string $token): void
    {
        $this->token = $token;

        // Recreate client with new token preserving original options
        $this->client = $this->createClient();
    }
}
