# Billing Module - Production Architecture and Operations

## Status

This document describes the production target for the Yii2 billing module reviewed from `billing_module.txt`.

Do not enable the production automatic-billing cron until all items in the **Production lock gate** are complete.

## 1. Source-of-truth records

| Record | Responsibility |
|---|---|
| `BillingRun` | One execution of automatic billing |
| `BillingEvent` | One renewable asset/period that is eligible for billing |
| `Invoice` | Customer financial statement |
| `InvoiceItem` | Snapshot of each charge on an invoice |
| `Transaction` | Payment lifecycle |
| `ServiceRenewalHistory` | Service entitlement actually granted after payment |
| `CustomerBillingProfile` | Billing-address/GST snapshot source |

## 2. Core invariants

1. One automatic invoice per customer per billing run.
2. A renewable service period must produce at most one `BillingEvent`.
3. Automatic `InvoiceItem` rows must point to a `billing_event_id`.
4. Manual lines use `InvoiceItem::TYPE_CUSTOM` and never renew Domain, Hosting or Maintenance assets.
5. Carried-forward debt uses `TYPE_ARREARS` and `source_invoice_id`.
6. Only captured transactions reduce outstanding balance.
7. Partial payment does not renew a service.
8. Renewable services are fulfilled only when their invoice is fully paid.
9. Fulfilment must be idempotent.
10. Financial records are cancelled/statused, not physically deleted.
11. Automatic billing is protected by a mutex.
12. Every automatic event and invoice belongs to the `BillingRun` which produced it.

## 3. Automatic billing pipeline

```text
CronController::actionRunBilling()
        |
        v
MySQL mutex: miracle:auto-billing
        |
        v
AutoBillingService::run()
        |
        +--> create BillingRun (Running)
        |
        +--> BillingCollectorService::collect(runId, today)
        |       |
        |       +--> DomainBillingCollector
        |       +--> HostingBillingCollector
        |       +--> MaintenanceBillingCollector
        |       |
        |       +--> BillingEventService::create(runId, renewal)
        |
        +--> AutoInvoiceService::generatePendingInvoices(runId)
        |       |
        |       +--> one invoice per user in THIS run
        |       +--> CustomerBillingProfile snapshot
        |       +--> GST snapshot
        |       +--> renewal InvoiceItems
        |       +--> eligible arrears
        |       +--> totals + invoice number
        |       +--> events -> Invoiced
        |
        +--> BillingRun -> Completed

Failure:
        BillingRun -> Failed + failure_message
```

## 4. BillingRun rules

`BillingRun` has three lifecycle states:

- `Running`
- `Completed`
- `Failed`

A run starts before collection. `billing_run_id` is passed explicitly through collection and invoice generation. Invoice generation must never consume unrelated global pending events.

Recommended run-number format:

```text
BR-20261015-163700-8f2a31c9
```

The database unique constraint on `run_number` remains the final collision guard.

## 5. Collector rules

`BillingCollectorService` must coordinate all three renewable services:

- Domain
- Hosting
- Maintenance

Its public API is:

```php
public function collect(
    int $runId,
    ?DateTimeImmutable $today = null
): array
```

Each returned `BillableRenewal` is converted to a `BillingEvent` using:

```php
$this->eventService->create(
    $runId,
    $renewal
);
```

`BillingEventService` protects service-period idempotency by looking up an existing event by service type, service ID, period start and period end before inserting a new row.

## 6. Invoice generation rules

`AutoInvoiceService` must accept a run ID:

```php
public function generatePendingInvoices(
    int $runId
): array
```

The customer list and event lock query must both include:

```php
'billing_run_id' => $runId,
'status' => BillingEvent::STATUS_PENDING,
'invoice_id' => null,
```

The generated invoice must contain:

```php
$invoice->billing_run_id = $runId;
```

This guarantees that a retry/new run cannot silently absorb events from a different run.

## 7. Customer and tax snapshot

Both manual and automatic invoice creation must snapshot `CustomerBillingProfile` into `Invoice`.

Tax is an invoice-time snapshot. Use configured supplier state/state code and billing profile state/state code to choose:

- intra-state: CGST + SGST
- inter-state: IGST

Do not hard-code tax percentages inside `AutoInvoiceService`. Read them from the billing configuration already used by the manual service.

## 8. Invoice numbering

Manual and automatic invoices must use the same `InvoiceNumberService`.

Do not keep separate automatic-number generation logic. A single service prevents behavioural drift.

## 9. Outstanding balance / arrears

When automatic invoicing transfers previous unpaid debt:

1. find eligible previous invoice(s),
2. calculate outstanding from captured payments,
3. create `TYPE_ARREARS` item with `source_invoice_id`,
4. prevent duplicate transfer of the same source invoice,
5. mark the source invoice `Carried_Forward`,
6. perform the transfer inside the same DB transaction that creates the destination invoice.

## 10. Payments

Payment reconciliation is based on captured transactions.

```text
captured = 0             -> Unpaid
0 < captured < total     -> Partially_Paid
captured >= total        -> Paid
```

Only the transition to fully paid may trigger renewal fulfilment.

## 11. Renewal fulfilment

`RenewalFulfilmentService` is the only service allowed to grant renewable-service entitlement.

For each eligible `BillingEvent`:

1. lock/read the paid invoice and event state,
2. skip an already fulfilled event,
3. resolve the Domain/Hosting/Maintenance asset,
4. write `ServiceRenewalHistory`,
5. update asset `current_expiry_date` to the event period end,
6. mark event `Fulfilled`,
7. commit once after all work succeeds,
8. roll back on any exception.

The legacy `InvoiceAutomatedRenewalBehavior` must not be attached to `Invoice` and should be removed after deployment verification.

## 12. Manual invoice workflow

```text
Admin Create Invoice
        |
        v
ManualInvoiceService::create()
        |
        +--> validate ManualInvoiceItemForm[]
        +--> CustomerBillingProfile snapshot
        +--> GST snapshot
        +--> Invoice save
        +--> TYPE_CUSTOM rows
        +--> total calculation
        +--> InvoiceNumberService
        +--> commit
```

Manual invoice update is allowed only when:

- status is `Unpaid`,
- no captured payment exists,
- invoice contains no automatic renewal item,
- invoice contains no carried-forward arrears.

Only approved editable header fields and `TYPE_CUSTOM` line items may change.

## 13. Cancellation

Invoice cancellation is a financial state transition, not deletion.

- `invoice/cancel` must be POST-only.
- Paid/fulfilled financial history must remain intact.
- Unfulfilled automatic events may be cancelled according to `InvoiceCancellationService` rules.
- Cancellation must never extend an asset.

## 14. Cron

Production command:

```bash
/usr/local/bin/php /home/rpocncwk/miracle/yii cron/run-billing >> /home/rpocncwk/miracle/runtime/logs/billing-cron.log 2>&1
```

The command is protected by the configured Yii MySQL mutex using lock name:

```text
miracle:auto-billing
```

Keep `CronController::actionRunBilling()` as the operational entry point. Avoid maintaining a second production billing command unless it has a distinct documented purpose.

## 15. Logging and failures

Log at minimum:

- run ID/run number,
- automatic billing start/completion/failure,
- generated invoice IDs/numbers,
- caught exception with stack context through `Yii::error()`.

Do not log payment secrets or credentials.

A failed run must persist `BillingRun::STATUS_FAILED` and a bounded `failure_message` where possible, then rethrow the original failure to the cron/controller boundary.

## 16. Production lock gate

Before enabling the cron, verify all of these:

- [ ] `BillingCollectorService` accepts `runId`.
- [ ] Maintenance collector is included.
- [ ] `BillingEventService::create($runId, $renewal)` is called correctly.
- [ ] `AutoBillingService` creates/finishes/fails `BillingRun`.
- [ ] `AutoInvoiceService` scopes every event query by `billing_run_id`.
- [ ] `Invoice.billing_run_id` is populated for automatic invoices.
- [ ] Automatic invoices use `CustomerBillingProfile`.
- [ ] Automatic invoices use state-driven configured GST.
- [ ] Automatic invoices use `InvoiceNumberService`.
- [ ] Arrears integration is transactional and idempotent.
- [ ] `RenewalFulfilmentService` rolls back on exception.
- [ ] Legacy `InvoiceAutomatedRenewalBehavior` is not attached.
- [ ] `cancel` is POST-only.
- [ ] Manual create/update integration tests pass.
- [ ] Automatic Domain integration test passes.
- [ ] Automatic Hosting integration test passes.
- [ ] Automatic Maintenance integration test passes.
- [ ] Same customer with all three services receives one invoice for one run.
- [ ] Re-running billing does not duplicate a billed period.
- [ ] Partial payment does not fulfil events.
- [ ] Full payment fulfils each event exactly once.
- [ ] Forced fulfilment failure leaves no partial renewal history/expiry change.
- [ ] Arrears are carried forward only once.
- [ ] Cancellation never extends service.

## 17. Deployment order

1. Back up the production database.
2. Deploy migrations/models and billing services with cron still disabled.
3. Run migrations.
4. Verify `billing_settings` and customer billing profiles.
5. Run automated billing tests against the test database.
6. Run the billing command against a staging/test database with known Domain, Hosting and Maintenance fixtures.
7. Verify `billing_run`, `billing_event`, `invoice`, `invoice_item`, `transaction` and `service_renewal_history` rows.
8. Run the command a second time and confirm no duplicate service periods/invoices are created.
9. Test partial and full payment fulfilment.
10. Test cancellation and forced rollback paths.
11. Enable the production cron only after the lock gate is green.
12. Review the first production run in `billing-cron.log` and the corresponding `BillingRun` record.

## 18. Files to retire after verification

- `common/components/behaviors/InvoiceAutomatedRenewalBehavior.php`
- `backend/views/invoice/invoice.php`
- `backend/views/invoice/view_1.php`

If `console/controllers/BillingController.php` duplicates the same production flow, retire it and keep `CronController::actionRunBilling()` as the single scheduled entry point.

## 19. Change-control rule after lock

After the lock gate passes, changes to billing amounts, service periods, invoice status transitions, payment reconciliation, arrears or fulfilment should ship with:

1. a migration when persistence changes,
2. an automated test reproducing the business rule,
3. backward-compatible treatment of existing invoices/events,
4. no destructive rewrite of financial history.
