# miracle

Customer portal and admin panel for domain, hosting and maintenance billing
(Yii 2 advanced template, PHP 8.4.1+, MySQL 8).

- `frontend/` — customer portal (services, invoices, payments, support)
- `backend/` — admin panel
- `console/` — cron jobs (`cron/run-billing`, `cron/payment-reminders`,
  `cron/send-reminders`, `cron/whatsapp-cleanup`, `cron/razorpay-sync`, `backup/run`) and migrations
- `common/services/` — billing, notification and support logic

Billing rules: [docs/BILLING.md](docs/BILLING.md), [docs/BILLING_MODULE.md](docs/BILLING_MODULE.md).
Schema: [docs/DATABASE_DICTIONARY.md](docs/DATABASE_DICTIONARY.md).

## Setup

```bash
composer2 install
php init                 # generates /yii and local config
cp .env.example .env     # then fill it in
php yii migrate          # a fresh install needs ADMIN_INITIAL_PASSWORD in .env
```

Deploy on the server (needs `rsync`):

```bash
cd /home/rpocncwk/repositories/miracle && bash deploy.sh   # fetches origin/main itself
```
