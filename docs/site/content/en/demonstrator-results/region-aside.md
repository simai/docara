---
noindex: true
---

# Payment notifications

Acme Pay sends a notification to your URL every time the status of a payment
or refund changes. This way the store learns about a payment without polling the API.

## Setup

Set the notification URL in your dashboard. The URL must use
HTTPS and respond with code `200` within five seconds.

## Notification format

```json {title="Request body"}
{
  "event": "payment.succeeded",
  "payment_id": "pay_2Hk81",
  "amount": "1500.00"
}
```

## Retries

If your server does not respond, Acme Pay retries after 1, 5 and 30 minutes,
and then once an hour for a day.
