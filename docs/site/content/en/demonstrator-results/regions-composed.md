---
noindex: true
---

# Refund a payment

You can refund money in full or in part within 180 days after payment.
A refund is created with a separate request and gets its own status.

## Full refund

Pass the payment ID. The refund amount equals the payment amount.

```bash {title="Request"}
curl https://api.acme-pay.example/v1/refunds \
  -u "$ACME_TEST_KEY:" \
  -d payment_id=pay_2Hk81
```

## Partial refund

Add the `amount`. Several partial refunds cannot exceed the
payment amount.

## Refund statuses

| Status | Meaning |
|---|---|
| `pending` | The bank is still processing the refund |
| `succeeded` | The money has been sent to the customer |
| `canceled` | The bank declined the refund |
