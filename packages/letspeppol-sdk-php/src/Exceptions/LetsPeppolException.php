<?php

namespace LetsPeppolSdk\Exceptions;

use Exception;
use Throwable;

/**
 * Base exception for all LetsPeppol SDK errors
 */
class LetsPeppolException extends Exception
{
    protected array $responseData;
    protected ?string $endpoint = null;
    protected ?string $method = null;

    public function __construct(
        string $message,
        int $code = 0,
        array $responseData = [],
        ?Throwable $previous = null,
        ?string $endpoint = null,
        ?string $method = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->responseData = $responseData;
        $this->endpoint = $endpoint;
        $this->method = $method;
    }

    /**
     * Get the response data from the failed request
     *
     * @return array Response data including error details
     */
    public function getResponseData(): array
    {
        return $this->responseData;
    }

    /**
     * Get the HTTP status code
     *
     * @return int HTTP status code (0 for network errors)
     */
    public function getStatusCode(): int
    {
        return $this->getCode();
    }

    /**
     * Get the API endpoint that caused the error
     *
     * @return string|null API endpoint
     */
    public function getEndpoint(): ?string
    {
        return $this->endpoint;
    }

    /**
     * Get the HTTP method used
     *
     * @return string|null HTTP method (GET, POST, etc.)
     */
    public function getMethod(): ?string
    {
        return $this->method;
    }

    /**
     * Check if this is a network error (vs API error)
     *
     * @return bool True if network error
     */
    public function isNetworkError(): bool
    {
        return $this->getCode() === 0;
    }

    /**
     * Check if this is a client error (4xx)
     *
     * @return bool True if client error
     */
    public function isClientError(): bool
    {
        return $this->getCode() >= 400 && $this->getCode() < 500;
    }

    /**
     * Check if this is a server error (5xx)
     *
     * @return bool True if server error
     */
    public function isServerError(): bool
    {
        return $this->getCode() >= 500 && $this->getCode() < 600;
    }

    /**
     * Get a detailed error report
     *
     * @return array Comprehensive error information
     */
    public function getErrorReport(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'endpoint' => $this->endpoint,
            'method' => $this->method,
            'is_network_error' => $this->isNetworkError(),
            'is_client_error' => $this->isClientError(),
            'is_server_error' => $this->isServerError(),
            'response_data' => $this->responseData,
            'previous_exception' => $this->getPrevious() ? get_class($this->getPrevious()) : null,
        ];
    }
}
