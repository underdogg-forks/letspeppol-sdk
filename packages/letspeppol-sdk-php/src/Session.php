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

    /**
     * Create a new Session instance configured with optional JWT token and API base URL.
     *
     * @param string $baseUrl Base API URL
     * @param string|null $token Optional JWT token for authenticated requests
     * @param array $clientOptions Optional Guzzle client options to merge with defaults
     */
    public function __construct(string $baseUrl, ?string $token = null, array $clientOptions = [])
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->token = $token;

        // Set default headers
        $defaults = [
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'User-Agent' => 'LetsPeppol PHP SDK',
            ],
            'timeout' => 30,
            'http_errors' => false, // Handle errors manually
        ];

        // Add token if provided
        if ($this->token) {
            $defaults['headers']['Authorization'] = "Bearer {$this->token}";
        }

        $options = array_replace_recursive($defaults, $clientOptions);
        $this->client = new Client($options);
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
     * Set the JWT token and recreate the client with the new token.
     *
     * @param string $token JWT token
     * @return void
     */
    public function setToken(string $token): void
    {
        $this->token = $token;

        // Recreate client with new token
        $options = $this->client->getConfig();
        $options['headers']['Authorization'] = "Bearer {$token}";
        $this->client = new Client($options);
    }
}
