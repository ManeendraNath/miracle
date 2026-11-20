# Billing Portal: setup guide

Your own customer portal and billing on your hosting. These steps are for cPanel hosting, or any Linux hosting
where PHP runs as your own account (the usual on shared hosting). Allow about an hour.

## What you need

- PHP 8.4.1 or newer, with the extensions pdo_mysql, mbstring, curl, openssl, intl, gd, zip and fileinfo
  (cPanel > Select PHP Version). The installer checks them.
- MySQL 8 (or MariaDB 10.6+).
- SSH or the cPanel Terminal, to run one command.
- Two addresses on your domain, for example `billing.yourdomain.com` for customers and `admin.yourdomain.com`
  for you and your staff, each with an SSL certificate (cPanel > SSL/TLS Status > Run AutoSSL).
- An email account to send from, for example `billing@yourdomain.com` (cPanel > Email Accounts).
- Your licence key, from **My Purchases** where you bought the portal.

## 1. Database

In cPanel > **MySQL Databases**, create a database and a user with a strong password, and add the user to the
database with **All Privileges**. Note the three names and the password.

## 2. Files

1. Upload `billing-portal-x.y.z.zip` to your home folder (File Manager > Upload). Never inside `public_html`:
   the folder holds your passwords (`.env`), and only its `frontend/web` and `backend/web` belong on the web.
2. Extract it. You get a folder `billing-portal`.
3. In cPanel > **Domains**, create the two addresses and set their document roots:
   - customer portal → `billing-portal/frontend/web`
   - admin panel → `billing-portal/backend/web`

## 3. Install

Open the Terminal (or SSH), then:

```bash
cd ~/billing-portal
php install.php
```

If it says the command line runs an older PHP, use your host's PHP 8.4.1+ path instead of `php`, for example
`/usr/local/bin/ea-php84 install.php` on cPanel.

It checks the server, asks for the database, the two addresses, the email account, your business name and GST
state, your licence key and a password for the first admin account, then creates everything. If a step fails, fix
what it says and run it again: finished steps are kept.

## 4. Cron jobs

In cPanel > **Cron Jobs**, add these lines. Replace `/home/USER` with your home folder (shown at the top of
File Manager) and `/usr/local/bin/php` with the path your host gives for PHP 8.4.1+.

```bash
0 2 * * *    /usr/local/bin/php /home/USER/billing-portal/yii backup/run >> /home/USER/billing-portal/runtime/logs/billing-cron.log 2>&1
0 3 * * *    /usr/local/bin/php /home/USER/billing-portal/yii cron/run-billing >> /home/USER/billing-portal/runtime/logs/billing-cron.log 2>&1
0 9 * * *    /usr/local/bin/php /home/USER/billing-portal/yii cron/payment-reminders >> /home/USER/billing-portal/runtime/logs/billing-cron.log 2>&1
30 9 * * *   /usr/local/bin/php /home/USER/billing-portal/yii cron/send-reminders >> /home/USER/billing-portal/runtime/logs/billing-cron.log 2>&1
0 10 * * *   /usr/local/bin/php /home/USER/billing-portal/yii cron/profile-reminders >> /home/USER/billing-portal/runtime/logs/billing-cron.log 2>&1
*/30 * * * * /usr/local/bin/php /home/USER/billing-portal/yii cron/razorpay-sync >> /home/USER/billing-portal/runtime/logs/billing-cron.log 2>&1
0 4 * * 0    /usr/local/bin/php /home/USER/billing-portal/yii cron/whatsapp-cleanup >> /home/USER/billing-portal/runtime/logs/billing-cron.log 2>&1
20 5 * * *   /usr/local/bin/php /home/USER/billing-portal/yii cron/licence-check >> /home/USER/billing-portal/runtime/logs/billing-cron.log 2>&1
```

The admin panel's **Cron Jobs & Logs** page shows when each last ran.

## 5. First sign-in

1. Open the admin address and sign in as **superadmin** with the password you chose.
2. Turn on two-factor sign-in under **My account** (the person icon, top right), and add accounts for your
   staff with their roles.
3. **Site Settings**: your phone (needed before the page can be saved), address, founding year, Grievance Officer,
   and the addresses of your own terms, privacy and refund pages (the portal links to them).
4. Your logo: upload a PNG about 400 pixels wide as `billing-portal/frontend/web/brand/logo.png`. It shows in the
   customer portal and on invoices. For the dark admin sidebar and email header, add a light version as
   `logo-white.png` in the same folder; a `favicon.ico` there becomes the browser icon. Until you add a logo, your
   business name is shown. Updates never replace these files.
5. Remove the line `ADMIN_INITIAL_PASSWORD=...` from `billing-portal/.env`.

## 6. Payments and other settings

These go in `billing-portal/.env` (File Manager > Edit); `.env.example` lists them all with notes:

- **Bank and UPI details** for invoices: `BILLING_BANK_NAME`, `BILLING_BANK_ACCOUNT_NAME`,
  `BILLING_BANK_ACCOUNT_NUMBER`, `BILLING_BANK_IFSC`, `BILLING_UPI_ID`; `BILLING_LUT_ARN` if you export under LUT.
- **Razorpay** (cards, UPI, net banking): `RAZORPAY_KEY_ID`, `RAZORPAY_KEY_SECRET`, `RAZORPAY_WEBHOOK_SECRET`.
  In the Razorpay dashboard, point a webhook at `https://billing.yourdomain.com/webhook/razorpay`.
- **PayPal** for clients abroad: `PAYPAL_CLIENT_ID`, `PAYPAL_SECRET`.
- **WhatsApp** alerts: `WHATSAPP_ENABLED`, `WHATSAPP_PROVIDER`, `WHATSAPP_API_KEY`.

Then switch each payment method on for clients in India or abroad in **Site Settings > Online payments**. Never share the
`.env` file: it holds your passwords and keys.

## Updates

Your subscription includes every new version (**My Purchases** > Download). To update:

1. Take a backup first: `php yii backup/run` in the Terminal.
2. Rename the folder `billing-portal` to `billing-portal-old`, and extract the new zip next to it. (A fresh folder,
   so nothing a new version removed is left behind.)
3. Move these from `billing-portal-old` into the new `billing-portal`: the file `.env`, and the folders `storage`,
   `backups`, `frontend/web/brand` and `frontend/web/uploads` (photos uploaded in the admin panel).
4. Run `php install.php` from the new `billing-portal` folder. It keeps your `.env`, sets up the folders the
   portal writes to (the cron jobs log into `runtime/logs`) and updates the database. Then check the portal and
   admin panel. (It also carries over the PHP version cPanel set for the two addresses; if a page shows a server
   error, choose PHP 8.4.1+ for them again in cPanel > MultiPHP Manager.)
5. Once all is well, delete `billing-portal-old`.

## Your licence

The portal checks its licence key with us once a day (it sends only the key and the portal's address). If the
subscription ends, the portal keeps working, including all billing for your customers, but the admin panel shows a
reminder and updates and support stop. To move the portal to another address, ask us in a support ticket.

## Help

Open a support ticket from your account where you bought the portal, with what you did and the exact message you
saw. If every page shows a server error straight after installing on a server where PHP runs as a different user from
the one owning the files (not the usual cPanel set-up), that user cannot read `.env` (mode 640): ask your host to
put it in the file's group. The log of the daily jobs is in `runtime/logs/billing-cron.log`; errors are in `frontend/runtime/logs` and
`backend/runtime/logs`.
