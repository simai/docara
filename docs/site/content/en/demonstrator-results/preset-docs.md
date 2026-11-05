---
noindex: true
---

# Quick start

In ten minutes you will accept your first test payment: get a key, create a
payment and check the notification.

## 1. Get a test key

Open your dashboard, go to "Keys" and click "Create test key".
A test key starts with `test_`.

## 2. Create a payment

```bash {title="Request"}
curl https://api.acme-pay.example/v1/payments \
  -u "$ACME_TEST_KEY:" \
  -d amount=100.00 \
  -d currency=RUB
```

## 3. Pay with a test card

Open `confirmation_url` from the response and enter the card `4111 1111 1111 1111`
with any expiry date in the future.
