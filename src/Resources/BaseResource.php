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
     * Make an API request with robust error handling
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $options Guzzle request options
     * @return array Response data
     * @throws ApiException When API request fails, network error occurs, or invalid response
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
                
                // Check for JSON decode errors
                if (json_last_error() !== JSON_ERROR_NONE && $body !== '') {
                    throw new ApiException(
                        "Invalid JSON response: " . json_last_error_msg(),
                        $statusCode,
                        ['body' => $body, 'json_error' => json_last_error_msg()]
                    );
                }
                
                return $decoded ?? [];
            }

            // Error status codes - attempt to parse error response
            $errorData = [];
            $errorMessage = $body;
            
            // Try to parse JSON error response
            if ($body !== '') {
                $decoded = json_decode($body, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $errorData = $decoded;
                    // Extract error message from common response formats
                    $errorMessage = $this->extractErrorMessage($decoded) ?? $body;
                }
            }

            // Create detailed error message based on status code
            $errorPrefix = $this->getErrorPrefix($statusCode);
            throw new ApiException(
                "{$errorPrefix}: {$statusCode} - {$errorMessage}",
                $statusCode,
                $errorData
            );
        } catch (ApiException $e) {
            throw $e;
        } catch (GuzzleException $e) {
            // Categorize network errors
            $errorType = $this->categorizeGuzzleException($e);
            throw new ApiException(
                "{$errorType}: {$e->getMessage()}",
                $e->getCode(),
                ['exception_class' => get_class($e), 'error_type' => $errorType],
                $e
            );
        }
    }

    /**
     * Extract error message from API response
     *
     * @param array $data Response data
     * @return string|null Error message
     */
    private function extractErrorMessage(array $data): ?string
    {
        // Common error message fields
        $fields = ['message', 'error', 'error_description', 'detail', 'title'];
        
        foreach ($fields as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                return $data[$field];
            }
        }
        
        // Check nested error objects
        if (isset($data['error']) && is_array($data['error'])) {
            return $this->extractErrorMessage($data['error']);
        }
        
        return null;
    }

    /**
     * Get error prefix based on HTTP status code
     *
     * @param int $statusCode HTTP status code
     * @return string Error prefix
     */
    private function getErrorPrefix(int $statusCode): string
    {
        return match (true) {
            $statusCode === 400 => 'Bad Request',
            $statusCode === 401 => 'Unauthorized',
            $statusCode === 403 => 'Forbidden',
            $statusCode === 404 => 'Not Found',
            $statusCode === 409 => 'Conflict',
            $statusCode === 422 => 'Validation Error',
            $statusCode === 429 => 'Rate Limit Exceeded',
            $statusCode >= 500 => 'Server Error',
            default => 'API Error',
        };
    }

    /**
     * Categorize Guzzle exceptions for better error messages
     *
     * @param GuzzleException $e Guzzle exception
     * @return string Error type
     */
    private function categorizeGuzzleException(GuzzleException $e): string
    {
        // Use instanceof for more reliable type checking
        if ($e instanceof \GuzzleHttp\Exception\ConnectException) {
            return 'Connection Error';
        }
        if ($e instanceof \GuzzleHttp\Exception\ServerException) {
            return 'Server Error';
        }
        if ($e instanceof \GuzzleHttp\Exception\ClientException) {
            return 'Client Error';
        }
        if ($e instanceof \GuzzleHttp\Exception\TooManyRedirectsException) {
            return 'Too Many Redirects';
        }
        if ($e instanceof \GuzzleHttp\Exception\RequestException) {
            return 'Request Error';
        }
        
        return 'Network Error';
    }

    /**
     * Make an API request that returns raw body content (not JSON)
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $options Guzzle request options
     * @return string Response body
     * @throws ApiException When API request fails or network error occurs
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
            $errorPrefix = $this->getErrorPrefix($statusCode);
            throw new ApiException(
                "{$errorPrefix}: {$statusCode} - " . substr($body, 0, 200),
                $statusCode,
                ['body_preview' => substr($body, 0, 500)]
            );
        } catch (GuzzleException $e) {
            $errorType = $this->categorizeGuzzleException($e);
            throw new ApiException(
                "{$errorType}: {$e->getMessage()}",
                $e->getCode(),
                ['exception_class' => get_class($e)],
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
     * @throws ApiException When API request fails or network error occurs
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
            $errorPrefix = $this->getErrorPrefix($statusCode);
            throw new ApiException(
                "{$errorPrefix}: {$statusCode} - {$body}",
                $statusCode,
                ['body' => $body]
            );
        } catch (GuzzleException $e) {
            $errorType = $this->categorizeGuzzleException($e);
            throw new ApiException(
                "{$errorType}: {$e->getMessage()}",
                $e->getCode(),
                ['exception_class' => get_class($e)],
                $e
            );
        }
    }
}
