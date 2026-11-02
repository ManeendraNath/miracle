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
