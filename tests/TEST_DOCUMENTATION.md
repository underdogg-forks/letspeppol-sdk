# PHPUnit Test Suite Documentation

This directory contains comprehensive PHPUnit tests for the LetsPeppol SDK.

## Test Structure

The test suite is organized into three main categories:

### 1. Unit Tests (`tests/Unit/`)
Unit tests focus on testing individual methods and classes in isolation using mocks. Each test class covers a specific subject area:

- **AuthenticationTest.php** - Tests authentication functionality
  - Successful authentication with token return
  - Authentication failure handling
  - Token management (set/get)
  - Token display formatting

- **DocumentManagementTest.php** - Tests document management operations
  - Listing documents with filters
  - Marking documents as read
  - Processing unread invoices
  - Error handling for missing documents

- **PartnerSearchTest.php** - Tests partner search and management
  - Finding partners by Peppol ID
  - Handling partner not found scenarios
  - Listing all partners
  - Error handling for invalid searches

- **ReceiveDocumentsTest.php** - Tests document receiving from proxy
  - Receiving new documents from Peppol network
  - Marking documents as downloaded
  - Processing multiple documents
  - Handling empty document lists

- **SendInvoiceTest.php** - Tests invoice sending workflow
  - UBL XML validation (success and failure)
  - Document creation as draft
  - Document sending
  - Complete send workflow
  - Error handling

### 2. Feature Tests (`tests/Feature/`)
Feature tests validate complete workflows and integration scenarios:

- **AuthenticationWorkflowTest.php** - End-to-end authentication flow
  - Complete authentication workflow
  - Account info retrieval after authentication
  - Error handling throughout the workflow

- **DocumentManagementWorkflowTest.php** - Document management workflows
  - Listing and processing unread invoices
  - Retrieving document details
  - Marking documents as paid
  - Filtering by multiple criteria

- **PartnerManagementWorkflowTest.php** - Partner management workflows
  - Searching for partners
  - Creating partners when not found
  - Updating existing partners
  - Deleting partners

- **ReceiveDocumentsWorkflowTest.php** - Document receiving workflows
  - Receiving and processing new documents
  - Marking documents as downloaded
  - Batch operations
  - Status updates retrieval

- **CompleteInvoiceWorkflowTest.php** - Complete invoice lifecycle
  - Full workflow from authentication to sending
  - Validation, creation, and sending steps
  - Statistics retrieval
  - Document scheduling

### 3. Legacy Tests (Root level)
Existing tests maintained for backward compatibility:
- `ConfigTest.php`
- `GuzzleClientTest.php`
- `LetsPeppolClientTest.php`
- `SessionTest.php`

## Test Coverage

The test suite covers all major examples from the SDK documentation:

1. **authenticateExample()** - Authentication workflow
2. **documentManagementExample()** - Document listing and management
3. **partnerSearchExample()** - Partner search functionality
4. **receiveDocumentsExample()** - Receiving documents from proxy
5. **sendInvoiceExample()** - Complete invoice sending workflow
6. **Complete workflow** - End-to-end scenario from authentication to statistics

## Running Tests

### Run All Tests
```bash
./vendor/bin/phpunit
```

### Run Unit Tests Only
```bash
./vendor/bin/phpunit --testsuite Unit
```

### Run Feature Tests Only
```bash
./vendor/bin/phpunit --testsuite Feature
```

### Run Legacy Tests Only
```bash
./vendor/bin/phpunit --testsuite Legacy
```

### Run with Detailed Output
```bash
./vendor/bin/phpunit --testdox
```

### Run Specific Test Class
```bash
./vendor/bin/phpunit tests/Unit/AuthenticationTest.php
```

## Test Statistics

- **Total Tests**: 61
- **Unit Tests**: 29
- **Feature Tests**: 32
- **Total Assertions**: 280+
- **Success Rate**: 100%

## Key Testing Principles

1. **Isolation**: Unit tests use mocks to isolate the system under test
2. **Clarity**: Test names clearly describe what is being tested
3. **Coverage**: Tests cover both success and failure scenarios
4. **Realism**: Feature tests simulate real-world workflows
5. **Maintainability**: Tests are organized by subject for easy maintenance

## Mock Strategy

Tests use PHPUnit's MockBuilder to create mock objects:
- Mock only the specific methods being tested
- Use `willReturn()` for successful scenarios
- Use `willThrowException()` for error scenarios
- Use `willReturnCallback()` for dynamic responses

## Test Naming Convention

All test methods follow the pattern:
```php
public function it_<describes_what_is_tested>(): void
```

Examples:
- `it_authenticates_successfully_and_returns_token`
- `it_lists_documents_with_filters`
- `it_handles_partner_not_found_scenario`

## Adding New Tests

When adding new tests:

1. **For Unit Tests**: Create or update files in `tests/Unit/`
2. **For Feature Tests**: Create or update files in `tests/Feature/`
3. Use the `LetsPeppolSdk\Tests\Unit` or `LetsPeppolSdk\Tests\Feature` namespace
4. Extend `PHPUnit\Framework\TestCase`
5. Follow the existing naming conventions
6. Add the `@test` annotation or prefix method with `test`
7. Include both success and failure scenarios

## Configuration

PHPUnit is configured via `phpunit.xml.dist`:
- Bootstrap: `vendor/autoload.php`
- Test suites: Unit, Feature, and Legacy
- Code coverage source: `src/` directory

## Dependencies

- PHPUnit 11.5.48 (or compatible version)
- PHP 8.1 or higher
- Composer for dependency management
