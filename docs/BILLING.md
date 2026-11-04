## Billing safety rules

### Never calculate billing in controllers

Controllers may invoke billing services but must not calculate renewal
prices, periods or invoice totals themselves.

### Never delete invoices

Invoices are financial history.

Use:

- Cancelled
- Refunded

instead.

### Invoice generation does not renew an asset

Creating an Unpaid invoice must not modify the Domain or Hosting expiry.

Expiry is advanced only when the renewal has actually been fulfilled.

### BillingEvent is the idempotency boundary

A renewable asset and billing period can have only one BillingEvent.

The database unique constraint is authoritative.

### Previous final billed amount is the renewal base

If a previous renewal was manually overridden, the overridden final
amount becomes the base for the next percentage increase.

The increase is yearly, not per renewal: domains and hosting go up at every renewal of a year or
longer and at every 12th monthly one; maintenance at the start of every 3-year block. If the cycle
length changed since the previous renewal (e.g. yearly to monthly), the previous amount no longer
fits, so the service's renewal base amount (set for the new cycle) is billed instead.

### Asset configuration overrides global configuration

NULL:

    use global BillingSettings

Value:

    use asset-specific value

### Renewal schedule never depends on cron execution date

Renewal periods are calculated from the asset's existing expiry date.

A late cron run must not shift the customer's service period.

### Same client receives one combined invoice

Pending Domain, Hosting and Maintenance BillingEvents for the same
client are grouped into one invoice during the same billing run.

Each renewal remains an independent InvoiceItem and BillingEvent.

## Online payments (Razorpay)

Turned on entirely from `.env`; no code change needed to go live:

```
RAZORPAY_ENABLED=true
RAZORPAY_KEY_ID=rzp_live_...        # rzp_test_... for test mode
RAZORPAY_KEY_SECRET=...
RAZORPAY_WEBHOOK_SECRET=...         # same value as the webhook secret in the Razorpay dashboard
```

Razorpay dashboard → Webhooks: URL `https://<customer site>/webhook/razorpay`, active event `payment.captured`.

Flow: invoice page "Pay Online" → `invoice/pay-online` creates a Razorpay order and a Pending
transaction (gateway 1) for the outstanding amount → Razorpay Checkout → the browser posts the
signed result to `invoice/razorpay-callback`, and Razorpay independently calls the webhook.
Both go through `common\services\payment\RazorpayService::capture()`, which is idempotent, so whichever arrives first
settles the invoice. An unpaid order is reused on the next attempt if the amount is unchanged.

Pending Razorpay transactions are unpaid orders, not payments: admins cannot approve or edit
them, and they do not block arrears carry-forward or UPI proof upload.

## Disabling instead of deleting

Domains, hosting, AMC, coupons and customers have no delete action. Use Enable/Disable
(`toggle-status`): invoices, billing events and renewal history keep pointing at the record.
Disabled services are skipped by billing, reminders and renewal monitoring; suspended
customers cannot log in.

## Coupons

Coupons are applied by an admin on manual invoices (coupon code field).

- **Applies To** (service types): empty = every line. When set, only invoice lines of
  those types are discounted (10% of the hosting line, not of the whole invoice), and a
  coupon with no matching line is rejected. Each manual invoice line has a service type.
- **Only For These Customers**: empty = anyone; otherwise only the listed customers.
- Usage limits: `0` means no limit.

Customers can also apply / remove a coupon themselves on the invoice page (any invoice,
including automatic renewals), via `InvoiceCouponService`. Allowed only while the invoice is
Unpaid, has no payment of any kind (captured, refunded or proof under review), and has no admin
discount without a coupon. An unpaid Razorpay order does not block it: it is superseded (the next
"Pay online" creates an order for the new total), and if the old checkout is still paid,
`RazorpayService::capture()` records it and alerts an admin about any excess. Editing a manual
invoice retires unpaid orders the same way; only a proof under review blocks the edit.
Previous-outstanding (arrears) lines are never discounted. Totals are recomputed with
`AutoInvoiceService::calculateInvoiceTotals()`.

Renewal invoices from the billing run also get, automatically, the newest active coupon an admin
has assigned to that customer ("Only For These Customers"), if it passes the same checks. A coupon
that doesn't fit is skipped; coupons assigned to nobody are never applied automatically.

## Foreign clients (export of services)

A billing profile whose **Country** is not India is billed as an export of services:

- No GST (`TaxCalculator::getRates()` returns 0 %, and `is_gst_applicable` is saved as 0).
- State is saved as "Foreign Country" with GST state code `96`; GSTIN and PAN are cleared;
  the postal code has no 6-digit PIN rule.
- Invoices keep the country (`invoice.client_country`; NULL on older invoices = India) and
  print an export note. With `BILLING_LUT_ARN` set in `.env` (this financial year's LUT, GST RFD-11)
  the note reads "supply meant for export under LUT without payment of IGST (LUT ARN ...)";
  renew the LUT every April and update the value.
- Amounts stay in INR.

## Admin roles

| Role | Access |
|---|---|
| Manager | Dashboard (without money figures), customers, domains, hosting, maintenance, renewals, support, leads, audit requests, notifications |
| Admin | Manager + invoices, payments, coupons, billing profiles, login history, cron log |
| Superadmin | Admin + administrator accounts, site settings |

The table lives in `BaseController::MIN_ROLE`; `RoleAccessTest` pins it. Every role can
change its own email and password under **My account** (user icon in the top bar).

### Two-factor sign-in (optional, per admin)

- **Turn on:** My account → *Set up two-factor sign-in* → scan the QR code (or type the key)
  in an authenticator app → enter the 6-digit code it shows. Turning it on signs out
  "remember me" logins on other devices.
- **Login:** after the password, the panel asks for a code (5 minutes to enter it). Wrong
  codes count toward the normal login lock (5 failures → 15 minutes). "Remember me" applies
  only after the code. Each code works once.
- **Turn off:** My account → enter a current code → *Turn off*.
- **Lost phone:** a Superadmin opens the admin's edit page → *Turn off two-factor*. If the
  only Superadmin is locked out: `php yii admin-2fa/reset <username>` on the server.
  Managers and Admins can't turn off anyone else's.

Codes are standard TOTP (RFC 6238, SHA1, 30 s), checked offline (`common\components\Totp`).
The secret is stored encrypted in `admin.totp_secret` with `TWO_FACTOR_KEY` from `.env`
(generate one with `php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'`). Without the key,
two-factor can't be set up. Secrets saved before encryption are encrypted at that admin's
next code. If the key is lost or changed, every admin's two-factor stops working: restore
the key, or run `php yii admin-2fa/reset <username>` for each.

## Admin WhatsApp alerts

With `WHATSAPP_ENABLED=true` and `ADMIN_WHATSAPP_MOBILE` set, urgent alerts (new ticket,
customer reply, new lead, audit request, unapplied online payment, failed automatic
invoice) are also sent as the `admin_alert` template: `{{1}}` = title, `{{2}}` = details
on one line. Create and get that template approved with the WhatsApp provider first.
Payment proofs keep their own `payment_submitted` template. Everything also goes to
`ADMIN_EMAIL` and the admin notification bell as before.
