# PHPUnit Tests

This directory contains PHPUnit tests for the LetsPeppol SDK.

## Test Files

### ConfigTest.php
Tests for the `Config` class:
- Default values
- Setting and getting endpoint, key, and logFile
- Independence of configuration values
- Resetting configuration

### GuzzleClientTest.php
Tests for the `GuzzleClient` class:
- Instantiation with and without logging
- Inheritance from GuzzleHttp\Client
- HTTP error handling (401, 500)
- Success responses (200, 201)
- Other error codes (400, 403, 404, etc.)
- Logging functionality
- Custom configuration support

### SessionTest.php
Tests for the `Session` class:
- Instantiation with various parameters
- Token management (get/set)
- Log file management (get/set)
- Base URL handling
- Client recreation when configuration changes
- Custom client options

### LetsPeppolClientTest.php
Tests for the `LetsPeppolClient` class:
- Instantiation with default and custom URLs
- Token management
- Log file support
- Access to KYC, Proxy, and App clients
- Factory methods (withToken)
- Client instance consistency

## Running Tests

Run all tests:

```bash
vendor/bin/phpunit tests/
```

Run with testdox format (readable output):

```bash
vendor/bin/phpunit tests/ --testdox
```

Run specific test file:

```bash
vendor/bin/phpunit tests/ConfigTest.php
```

Run specific test method:

```bash
vendor/bin/phpunit tests/ConfigTest.php --filter testConfigHasDefaultValues
```

## Test Coverage

57 tests covering:
- Config class (6 tests)
- GuzzleClient class (14 tests)
- Session class (17 tests)
- LetsPeppolClient class (20 tests)

All tests use PHPUnit assertions and mock objects where appropriate to avoid external dependencies.
