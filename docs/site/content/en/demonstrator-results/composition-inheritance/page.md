---
noindex: true
---

# Cancel a payment

You can cancel a payment in the `pending` status until the customer pays it.
A paid payment cannot be canceled: create a refund for it instead.

## Request

```bash {title="Request"}
curl -X POST https://api.acme-pay.example/v1/payments/pay_2Hk81/cancel \
  -u "$ACME_TEST_KEY:"
```

## Response

The payment moves to the `canceled` status, and the payment page stops
opening.

## Limits

You can cancel a payment within 24 hours after it is created. After that it
is canceled automatically.
