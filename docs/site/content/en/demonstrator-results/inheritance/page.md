---
noindex: true
---

# The Payment object

A payment stores the amount, currency, status and change history. All fields
come in the response to payment creation and in notifications.

| Field | Type | Example | Description |
|---|---|---|---|
| `id` | string | `pay_2Hk81` | Payment ID |
| `amount` | string | `1500.00` | Amount with two digits after the point |
| `currency` | string | `RUB` | Currency code per ISO 4217 |
| `status` | string | `succeeded` | `pending`, `succeeded` or `canceled` |
| `created_at` | date and time | `2026-11-02T10:15:00Z` | Creation time in UTC |
| `confirmation_url` | string | `https://pay.acme-pay.example/c/2Hk81` | Payment page for the customer |
