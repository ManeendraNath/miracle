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

The increase is not per renewal: it applies each time the renewals on the current cycle pass a block
of time: a year for domains, two years for hosting, three years for maintenance. Hosting renews monthly,
yearly or for several years (set per service) and goes up after every 24 monthly renewals, after every 2 yearly
ones (so the 3rd, 5th, ... billed here) and after each renewal of two years or longer, by one step whatever its length.
Domains go up after each renewal of a year or longer; yearly maintenance after every 3 (the 4th, 7th, ...).
The first renewal billed through this system is the base amount; the count starts there. If the cycle length changed since
the previous renewal (e.g. yearly to monthly), the previous amount no longer fits, so the service's
renewal base amount (set for the new cycle) is billed and the count starts again.

An Admin can set a one-time **Next Renewal Price** on a domain, hosting or maintenance service
(e.g. a negotiated rate). The next renewal is billed exactly that (a manual amount, so later
increases build on it) and the field clears once that renewal is paid. A renewal already invoiced
keeps its price: cancel that invoice first if the new price must apply to it. Clearing the field before
the renewal is invoiced returns it to the calculated price.

### Standard maintenance price

New maintenance services start at ₹4,000 per year (`Maintenance::STANDARD_YEARLY_AMOUNT`). An Admin can change it per
service. The global maintenance increase is 10% every three years; a service with its own increase uses that instead.

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
Disabling a service cancels its unbilled renewal and its unpaid renewal-only invoice. Invoices that also cover
other items or a carried-forward balance, or have a payment, stay open and staff are warned. Below Admin nothing
is cancelled on an invoice: it is reported to an Admin instead.
Disabled services are skipped by billing, reminders and renewal monitoring; suspended
customers cannot log in.

Only Active services are billed. A suspended hosting or overdue maintenance service is still listed for
renewal, but no renewal invoice is created until staff set it Active. If a service has no price, or billing is
off for its type, reminders ask the customer to contact us instead of promising an invoice.

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

## Incomplete billing profiles

`cron/profile-reminders` runs daily. Every active customer with no billing profile, or with legal name, address, city,
state or postal code blank, gets an email and a portal notification, starting the day after their account was created
and repeating daily until complete. Admins get one daily email and bell notification listing them all. Reminder
preferences don't apply: without a profile no renewal invoice can be created.

The same job suspends an account a month after signup if it is still incomplete, has nothing live (a service that is
active, suspended or overdue, an open invoice, an open ticket, or a payment held for refund or apply) and was warned at
least 7 days earlier: a first run only warns, however old the account. The customer is emailed and admins get a list.
Setting it Active again gives it another month, counted from that change of status. A suspended customer who enters the right
password is told the account is disabled; a wrong password gets the usual message.

### Billing details first

Email verification only proves the address. After it the customer logs in and lands on the billing form, and
`frontend\components\ProfileGate` keeps them there: until the details are complete every portal page redirects to the
form, except change password, support and logout. A customer with something live (a service, an open invoice, a held
payment) gets a banner instead of being blocked, so a bill can always be paid; so does anyone with an invoice on the
invoice and payment pages, so a receipt stays reachable. An open ticket doesn't unlock the portal. Staff creating a
customer send them a welcome email and are taken to the billing form next, with a **Customer Will Complete This** button
to leave it. Managers may use that form for a customer created in the last 30 days, but can only enter what a customer
could (no country, GST or discount), and admins are told to review GST.
Resending a verification email clears the password chosen at signup, because whoever signed up may not own the mailbox:
the email tells the customer to use Forgot Password after confirming.

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
| Manager | Dashboard (without money figures), customers (status only, not their login details; may add a new customer's billing details), domains, hosting, maintenance, renewals, support, leads and audit requests (view only), notifications |
| Admin | Manager + invoices, payments, coupons, billing profiles, login history, cron log |
| Superadmin | Admin + administrator accounts, site settings |

The table lives in `BaseController::MIN_ROLE`; `RoleAccessTest` pins it. Every role can
change its own email and password under **My account** (user icon in the top bar).

### Forgotten admin password

*Forgot password?* on the admin login emails a one-hour link to an active admin's address (built from
`BACKEND_BASE_URL`). Using it signs out other sessions, clears the login lock and alerts admins; two-factor
stays on. Mail not working: another Superadmin can set a new password on the admin's edit page.

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
