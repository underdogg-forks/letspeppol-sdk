<?php

namespace LetsPeppolSdk\Exceptions;

use Exception;

/**
 * Base exception for all LetsPeppol SDK errors
 */
class LetsPeppolException extends Exception
{
    protected array $responseData;

    public function __construct(string $message, int $code = 0, array $responseData = [], ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->responseData = $responseData;
    }

    /**
     * Get the response data from the failed request
     */
    public function getResponseData(): array
    {
        return $this->responseData;
    }
}
