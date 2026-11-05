---
noindex: true
---

# Install the PHP SDK

The SDK signs requests, retries on network errors and parses
API responses.

## Requirements

- PHP 8.2 or later;
- the `curl` and `json` extensions;
- a test key from your dashboard.

## Installation

```bash
composer require acme/pay-sdk
```

## First request

```php {title="pay.php"}
$client = new Acme\Pay\Client(getenv('ACME_TEST_KEY'));
$payment = $client->payments->create(['amount' => '1500.00', 'currency' => 'RUB']);
```
