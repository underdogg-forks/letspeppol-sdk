# LetsPeppol SDK Examples

This directory contains example scripts demonstrating how to use the LetsPeppol PHP SDK.

## Available Examples

### basic_usage.php

Demonstrates basic SDK usage including:
- Authentication
- Document management
- Partner search
- Receiving documents from proxy
- Sending invoices

## Running Examples

Make sure you have installed the dependencies:

```bash
composer install
```

Then run any example:

```bash
php examples/basic_usage.php
```

## Configuration

Before running the examples, make sure to:

1. Update credentials in the example files
2. Ensure you have access to a LetsPeppol account
3. Have valid UBL XML for document creation examples

## Note

These examples are for demonstration purposes. In production:
- Never hardcode credentials
- Use environment variables for configuration
- Implement proper error handling
- Add logging and monitoring
