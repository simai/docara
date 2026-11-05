---
noindex: true
---

# API error codes

If a request fails, the API responds with an HTTP code and an `error` object
with a machine-readable code and a message.

| Code | HTTP | What to do |
|---|---|---|
| `invalid_amount` | 400 | Pass the amount as a string with two digits after the point |
| `invalid_key` | 401 | Check the key and the mode: test or live |
| `payment_not_found` | 404 | Check the payment ID |
| `rate_limited` | 429 | Retry after the time in the `Retry-After` header |
