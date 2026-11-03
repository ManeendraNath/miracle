<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use common\components\CustomYii;
use yii\helpers\Html;

$name = Html::encode(CustomYii::getSetting('site_name') ?: 'Miracle Web Technologies');

echo $this->render('_legal', ['title' => 'Privacy Policy', 'updated' => '03 Nov 2026', 'body' => <<<HTML
<p>This policy explains what personal data {$name} collects through this website and customer portal, why, and how it is protected.</p>

<h2 class="h5 fw-bold mt-4">1. What we collect</h2>
<ul>
    <li><strong>Account and contact details:</strong> name, email address, phone number, company name and billing address.</li>
    <li><strong>Service records:</strong> your domains, hosting and maintenance plans, invoices, payments and support tickets.</li>
    <li><strong>Enquiries:</strong> what you send through the contact and website-audit forms.</li>
    <li><strong>Technical data:</strong> IP address, browser and log data needed to run and secure the site, a login history (time, IP address and browser) used to alert you to sign-ins from new devices, and a session cookie that keeps you signed in.</li>
</ul>

<h2 class="h5 fw-bold mt-4">2. Payments</h2>
<p>Online payments are processed by Razorpay. Card, UPI and bank details are entered on Razorpay's secure checkout and are never stored by us; we keep only the payment reference, amount and status. Payment proofs you upload for UPI or bank transfers are stored securely and seen only by our staff.</p>

<h2 class="h5 fw-bold mt-4">3. How we use it</h2>
<p>To provide and renew your services, raise invoices and record payments, send renewal reminders and service notices, answer support requests, and meet legal and accounting obligations. We do not sell your data or use it for third-party advertising.</p>

<h2 class="h5 fw-bold mt-4">4. Who we share it with</h2>
<p>Only with those needed to deliver the service: domain registrars and registries (registration details, as their rules require), our hosting and email providers, Razorpay for payments, and authorities where the law requires it.</p>

<h2 class="h5 fw-bold mt-4">5. How long we keep it</h2>
<p>For as long as you use our services, and afterwards for as long as tax and accounting law requires invoice and payment records to be kept.</p>

<h2 class="h5 fw-bold mt-4">6. Security</h2>
<p>The site uses HTTPS, passwords are stored only as secure hashes, and staff access is limited by role.</p>

<h2 class="h5 fw-bold mt-4">7. Your choices</h2>
<p>You can view and update your details in the customer portal, or contact us to correct or delete data we are not required to keep. You can turn reminder and notification emails on or off in the customer portal under My Profile &rarr; Notification Preferences.</p>

<h2 class="h5 fw-bold mt-4">8. Grievance Officer</h2>
<p>For any complaint or request about your personal data, contact our Grievance Officer:<br>
<strong>Maneendra Nath</strong><br>
Email: <a href="mailto:support@miraclewebtechnologies.com">support@miraclewebtechnologies.com</a>, <a href="mailto:antmnt.trivedi@gmail.com">antmnt.trivedi@gmail.com</a><br>
We acknowledge complaints within 2 working days and resolve them within 30 days.</p>

<h2 class="h5 fw-bold mt-4">9. Changes</h2>
<p>We will post any change to this policy on this page with a new "last updated" date.</p>
HTML]);
