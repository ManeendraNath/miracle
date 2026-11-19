<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Our billing portal, sold by subscription (kind software), added as a draft: staff upload the build (dist script),
 * check the wording and publish it. Monthly ₹1,499 / US$29, yearly ₹14,999 / US$290 (Nov 2026: WHMCS $35-55 a
 * month, Blesta $18-24, neither with Indian GST invoicing built in). Skipped if the slug is already there.
 */
final class m261219_110000_billing_portal_product extends Migration
{
    private const SLUG = 'billing-portal';

    public function safeUp(): void
    {
        // Our own catalogue: a copy sold to another business (PORTAL_ONLY) gets none of it.
        if (\common\components\PortalOnly::enabled()) {
            return;
        }
        if ((new \yii\db\Query())->from('{{%product}}')->where(['slug' => self::SLUG])->exists($this->db)) {
            return;
        }
        $this->insert('{{%product}}', [
            'name' => 'Billing Portal for Hosting & Web Businesses',
            'slug' => self::SLUG,
            'technology' => 'yii2',
            'kind' => 'software',
            'summary' => 'Your own customer portal and billing: domains, hosting and maintenance, GST invoices, online payments and automatic renewals.',
            'description' => "The billing system we run our own business on, for web hosting resellers, web agencies and freelancers in India. Your customers log in to see their services and renewal dates, pay invoices online, download receipts and raise support tickets; you manage it all from one admin panel.\n\n"
                . "It installs on your own hosting (PHP 8.2 or newer and MySQL 8), under your business name, for example at billing.yourdomain.com. Your website stays as it is: you link to the portal from it.\n\n"
                . "A subscription is for one installation and includes every new version and our support while it is paid. We can also install it on your hosting for you: ask us.",
            'features' => "Customer portal: services, renewal dates, invoices, receipts and support tickets\n"
                . "Domains, hosting and maintenance (AMC) with automatic renewal invoices\n"
                . "GST invoices: CGST and SGST or IGST by the customer's state, and export invoices under LUT\n"
                . "Online payments by Razorpay (cards, UPI, net banking) and PayPal for clients abroad, plus UPI or bank transfer with a payment proof\n"
                . "Prices in US dollars for clients abroad\n"
                . "Email and WhatsApp reminders before renewals and for unpaid invoices\n"
                . "Coupons, outstanding balances carried to the next invoice, refunds and write-offs\n"
                . "Invoice and payment exports (CSV) for GST filing\n"
                . "Staff accounts with roles and two-factor sign-in\n"
                . "Daily backups of the database",
            'version' => '1.0.0',
            'price_single_inr' => 1499,
            'price_single_usd' => 29,
            'price_unlimited_inr' => 14999,
            'price_unlimited_usd' => 290,
            'status' => 0, // Draft
            'sort_order' => 10,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
    }

    public function safeDown(): void
    {
        $this->delete('{{%product}}', ['and', ['slug' => self::SLUG, 'status' => 0], ['not in', 'id', (new \yii\db\Query())->select('product_id')->from('{{%product_order}}')]]);
    }
}
