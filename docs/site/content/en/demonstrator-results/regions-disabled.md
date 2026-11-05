---
noindex: true
---

# API terms of use

These terms apply to every store that accepts payments through
Acme Pay. Read them in full: a page without the menu and outline gives the text
the full width.

## 1. Access keys

The store keeps its keys secret and never sends them to the customer's browser. If
a key may have leaked, revoke it in your dashboard the same
day.

## 2. Customer data

Card numbers never pass through the store's server: customers enter them on the Acme Pay
payment page. The store receives only the last four digits and the expiry date.

## 3. Limits

No more than 100 requests per second per key. Above that, the API responds
with code `429`.
