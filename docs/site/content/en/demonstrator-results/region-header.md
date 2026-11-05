---
noindex: true
---

# Accept your first payment

Create a payment, send the customer to the payment page and wait for the
result notification. A test key is enough to try it: no money is
charged.

## 1. Create a payment

```bash {title="Request"}
curl https://api.acme-pay.example/v1/payments \
  -u "$ACME_TEST_KEY:" \
  -d amount=1500.00 \
  -d currency=RUB \
  -d return_url=https://shop.example/thanks
```

The response contains the payment page URL `confirmation_url`. Pass it to
the customer.

## 2. Wait for the result

| Status | Meaning |
|---|---|
| `pending` | The customer has not paid yet |
| `succeeded` | The money has been received |
| `canceled` | The customer declined or the time ran out |

:::alert {type=info}
#### Do not poll the API

The status arrives at your notification URL.
:::
