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
