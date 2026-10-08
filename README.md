# Magento 2 Guest Order Lookup & Returns

[![CI](https://github.com/ahsanhafeez1122-ship-it/magento2-guest-order-returns/actions/workflows/ci.yml/badge.svg)](https://github.com/ahsanhafeez1122-ship-it/magento2-guest-order-returns/actions/workflows/ci.yml)
![Magento 2.4](https://img.shields.io/badge/Magento-2.4.x-orange)
![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-777bb4)
![License: MIT](https://img.shields.io/badge/license-MIT-green)

Lets customers who checked out **as a guest** find their order and request a return themselves,
without creating an account or emailing support.

Most UK shoppers check out as guests. When they want to track a parcel or send something back,
they have no account to log in to, so they contact customer service. This module gives them a
self-service page instead, and gives the store team a grid to approve or reject returns.

## Features

**Storefront**
- **Order lookup** at `/guest-returns/lookup` with order number + checkout email + billing postcode
- UK-friendly postcode matching: `sw1a 1aa`, `SW1A1AA` and ` SW1A  1AA ` all match
- Order page with status, items, tracking numbers and past return requests
- **Return form**: choose items and quantities, a reason and an optional comment
- Footer link "Track or Return an Order"

**Rules that protect the store**
- Return window counted from the **latest shipment date** (configurable, default 30 days)
- Only `processing` / `complete` orders; cancelled, closed and on-hold orders are refused
- Returnable qty = shipped − refunded − already requested (rejected requests free the qty again)
- Virtual items (gift cards, downloads) are never returnable
- **Transaction-safe**: quantities are re-checked inside a DB transaction with the order row
  locked (`SELECT … FOR UPDATE`), so two tabs submitting at once can't return more than shipped

**Security**
- Brute-force protection: failed lookups are rate-limited per session (default 5 per 15 minutes)
- One generic error message whichever field was wrong, so the form can't be used to check
  whether an email or order number exists
- Constant-time comparison (`hash_equals`) for email and postcode
- Verified-order session expires (default 30 minutes), plus a "look up a different order" button
- CSRF form keys on every POST; all output escaped

**Admin**
- **Sales › Operations › Guest Returns** grid with filters, sorting and an items summary
- Mass actions: Approve, Reject, Mark as Completed, with the customer emailed on each change
- Separate ACL for viewing returns and changing their status
- Settings under **Stores › Configuration › Sales › Guest Returns**

**Emails** (all editable in Marketing › Email Templates)
- Customer: request received, status changed
- Store team: new request (optional recipient)

## How it works

```mermaid
sequenceDiagram
    actor C as Guest customer
    participant L as Lookup controller
    participant S as Session
    participant R as ReturnSubmitter
    participant DB as Database
    C->>L: order no. + email + postcode
    L->>S: failed attempts in window?
    L->>DB: find order (same store)
    L->>S: remember verified order (30 min)
    C->>R: items, qty, reason
    R->>DB: BEGIN; lock sales_order row
    R->>R: eligibility + qty rules
    R->>DB: insert request + lines; COMMIT
    R-->>C: email confirmation
```

## Installation

```bash
composer require ahsanhafeez/module-guest-returns
# or copy the repo to app/code/AhsanHafeez/GuestReturns

bin/magento module:enable AhsanHafeez_GuestReturns
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

## Configuration

| Setting | Default | Path |
|---|---|---|
| Enable | Yes | `guest_returns/general/enabled` |
| Return window (days) | 30 | `guest_returns/general/return_window_days` |
| Return reasons | 5 common reasons | `guest_returns/general/reasons` (one per line) |
| Max failed lookups | 5 | `guest_returns/security/max_attempts` |
| Failed lookup window (min) | 15 | `guest_returns/security/attempt_window_minutes` |
| Verified session lifetime (min) | 30 | `guest_returns/security/session_lifetime_minutes` |
| Send emails | Yes | `guest_returns/email/enabled` |
| Store team recipient | empty (off) | `guest_returns/email/admin_recipient` |

## Code structure

```
Api/                          Service contracts (ReturnRequestInterface, repository)
Controller/Lookup/            Lookup form, lookup POST, logout
Controller/Order/View.php     Verified order page
Controller/Returns/Submit.php Return form POST
Controller/Adminhtml/Request/ Grid page and mass status action
Model/Eligibility/            Return rules: plain PHP, no Magento dependency
Model/Lookup/                 Input validation, attempt limiter, order locator
Model/Request/                Quantity validation
Service/ReturnSubmitter.php   Transactional request creation
ViewModel/OrderView.php       Data for the order page template
etc/db_schema.xml             ahsan_guest_return_request, ahsan_guest_return_item
view/frontend|adminhtml       Layouts, templates, UI grid, email templates
Test/Unit/                    PHPUnit tests for the rules
```

The return rules (`Model/Eligibility`, `Model/Request`, `Model/Lookup`) are deliberately free of
Magento classes. The Magento layer only reads data and calls them. This keeps the rules easy to
read, and lets the unit tests run in CI without a full Magento install.

## Tests

```bash
php Test/static-check.php   # every PHP/PHTML file parses, every XML file is well-formed
phpunit                     # 18 unit tests for eligibility, quantities, lookup input and rate limiting
```

CI runs both on PHP 8.1, 8.2 and 8.3.

## Compatibility

Magento Open Source / Adobe Commerce 2.4.4 – 2.4.7, PHP 8.1 – 8.3. Works with Luma-based themes.
Hyvä needs its own templates.

## License

MIT, © Ahsan Hafeez
