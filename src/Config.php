<?php

namespace LetsPeppolSdk;

/**
 * Configuration class for LetsPeppol SDK
 * 
 * Stores global configuration settings such as API endpoint, key, and log file path.
 */
class Config
{
    /**
     * API endpoint URL
     */
    public static string $endpoint = '';

    /**
     * API key for authentication
     */
    public static string $key = '';

    /**
     * Log file path for request/response logging
     * 
     * When set, all HTTP requests and responses will be logged to this file.
     * When empty, logging is disabled.
     */
    public static string $log_file = '';
}
