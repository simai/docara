---
noindex: true
---

# Установка SDK для PHP

SDK берёт на себя подпись запросов, повторы при сетевых ошибках и разбор
ответов API.

## Требования

- PHP 8.2 или новее;
- расширения `curl` и `json`;
- тестовый ключ из личного кабинета.

## Установка

```bash
composer require acme/pay-sdk
```

## Первый запрос

```php {title="pay.php"}
$client = new Acme\Pay\Client(getenv('ACME_TEST_KEY'));
$payment = $client->payments->create(['amount' => '1500.00', 'currency' => 'RUB']);
```
