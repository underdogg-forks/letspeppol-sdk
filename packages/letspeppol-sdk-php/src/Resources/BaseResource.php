<?php

namespace LetsPeppolSdk\Resources;

use GuzzleHttp\Exception\GuzzleException;
use LetsPeppolSdk\Exceptions\ApiException;
use LetsPeppolSdk\Session;

/**
 * Base API client for LetsPeppol services
 */
abstract class BaseResource
{
    protected Session $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * Get the session
     */
    public function getSession(): Session
    {
        return $this->session;
    }

    /**
     * Make a GET request
     *
     * @param string $endpoint API endpoint
     * @param array $query Query parameters
     * @return array Response data
     * @throws ApiException
     */
    protected function get(string $endpoint, array $query = []): array
    {
        return $this->request('GET', $endpoint, ['query' => $query]);
    }

    /**
     * Make a POST request
     *
     * @param string $endpoint API endpoint
     * @param array $data Request body data
     * @param array $headers Additional headers
     * @return array Response data
     * @throws ApiException
     */
    protected function post(string $endpoint, array $data = [], array $headers = []): array
    {
        $options = ['json' => $data];
        if (!empty($headers)) {
            $options['headers'] = $headers;
        }
        return $this->request('POST', $endpoint, $options);
    }

    /**
     * Make a PUT request
     *
     * @param string $endpoint API endpoint
     * @param array $data Request body data
     * @return array Response data
     * @throws ApiException
     */
    protected function put(string $endpoint, array $data = []): array
    {
        return $this->request('PUT', $endpoint, ['json' => $data]);
    }

    /**
     * Make a DELETE request
     *
     * @param string $endpoint API endpoint
     * @return array Response data
     * @throws ApiException
     */
    protected function delete(string $endpoint): array
    {
        return $this->request('DELETE', $endpoint);
    }

    /**
     * Make an API request
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $options Guzzle request options
     * @return array Response data
     * @throws ApiException
     */
    protected function request(string $method, string $endpoint, array $options = []): array
    {
        try {
            $response = $this->session->getClient()->request($method, $endpoint, $options);
            $statusCode = $response->getStatusCode();
            $body = (string) $response->getBody();

            // Success status codes (2xx)
            if ($statusCode >= 200 && $statusCode < 300) {
                // Try to decode JSON, return as array if successful
                $decoded = json_decode($body, true);
                return $decoded ?? [];
            }

            // Error status codes
            $errorData = json_decode($body, true) ?? [];
            throw new ApiException(
                "API request failed: {$statusCode} - {$body}",
                $statusCode,
                $errorData
            );
        } catch (GuzzleException $e) {
            throw new ApiException(
                "Network error: {$e->getMessage()}",
                0,
                [],
                $e
            );
        }
    }

    /**
     * Make an API request that returns raw body content (not JSON)
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $options Guzzle request options
     * @return string Response body
     * @throws ApiException
     */
    protected function requestRaw(string $method, string $endpoint, array $options = []): string
    {
        try {
            $response = $this->session->getClient()->request($method, $endpoint, $options);
            $statusCode = $response->getStatusCode();
            $body = (string) $response->getBody();

            // Success status codes (2xx)
            if ($statusCode >= 200 && $statusCode < 300) {
                return $body;
            }

            // Error status codes
            throw new ApiException(
                "API request failed: {$statusCode} - {$body}",
                $statusCode
            );
        } catch (GuzzleException $e) {
            throw new ApiException(
                "Network error: {$e->getMessage()}",
                0,
                [],
                $e
            );
        }
    }

    /**
     * Make an API request and return response with headers
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $options Guzzle request options
     * @return array ['body' => string, 'headers' => array]
     * @throws ApiException
     */
    protected function requestWithHeaders(string $method, string $endpoint, array $options = []): array
    {
        try {
            $response = $this->session->getClient()->request($method, $endpoint, $options);
            $statusCode = $response->getStatusCode();
            $body = (string) $response->getBody();

            // Success status codes (2xx)
            if ($statusCode >= 200 && $statusCode < 300) {
                $headers = [];
                foreach ($response->getHeaders() as $name => $values) {
                    $headers[$name] = $values[0] ?? '';
                }
                return [
                    'body' => $body,
                    'headers' => $headers,
                ];
            }

            // Error status codes
            throw new ApiException(
                "API request failed: {$statusCode} - {$body}",
                $statusCode
            );
        } catch (GuzzleException $e) {
            throw new ApiException(
                "Network error: {$e->getMessage()}",
                0,
                [],
                $e
            );
        }
    }
}
