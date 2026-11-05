<?php

/** @var yii\web\View $this */

use yii\bootstrap5\Html;

$this->title = 'Miracle Web Technologies | Web Hosting, Domains & Website Maintenance';
?>
<div class="site-index" style="background-color: #f8fafc; color: #1e293b; font-family: 'Inter', system-ui, sans-serif;">

    <!-- Hero -->
    <div class="jumbotron text-center py-5 px-3 mb-5 border-0 position-relative shadow-sm" 
         style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-radius: 1rem; color: #ffffff;">
        <div class="container py-4">
            <span class="badge px-3 py-2 mb-3 text-uppercase fw-bold shadow-sm" style="letter-spacing: 1.5px; font-size: 0.8rem; background-color: #dc2626;">
                Hosting · Domains · Website Maintenance
            </span>
            <h1 class="display-3 fw-bold mb-3 tracking-tight" style="color: #ffffff;">
                Modern Systems. <span style="color: #00a3e0;">Absolute Stability.</span>
            </h1>
            <p class="lead mx-auto mb-4 text-slate-300" style="max-width: 800px; color: #cbd5e1; font-weight: 300; font-size: 1.25rem;">
                We modernise older web applications and look after your hosting, domains and website maintenance.
            </p>
            <div class="d-flex justify-content-center gap-3 flex-wrap pt-2">
                                <?= Html::a('Request a Website Audit', ['site/audit'], [
                                'class' => 'btn btn-lg px-4 py-3 fw-bold shadow-sm text-white',
                                'style' => 'background-color: #00a3e0 !important; border: none !important; min-width: 240px; border-radius: 0.5rem; color: #ffffff !important;'
                ]) ?>
                <a class="btn btn-outline-light btn-lg px-4 py-3 fw-bold" href="#hosting-tiers" style="border-color: #475569; color: #f1f5f9; min-width: 240px; border-radius: 0.5rem;">
                    See Hosting Plans
                </a>
            </div>
        </div>
    </div>

    <!-- Hosting plans -->
    <div id="hosting-tiers" class="container py-4">
        <div class="text-center mb-5">
            <h2 class="fw-bold text-dark h1 mb-2">Managed Hosting Plans</h2>
            <p class="text-muted fs-5 mx-auto" style="max-width: 600px;">
                Plans for business websites, custom applications and web agencies. Pay monthly, yearly or for several years; renewal prices may rise every two years.
            </p>
        </div>

        <div class="row g-4 align-items-stretch">
            <!-- Plan 1 -->
            <div class="col-lg-4">
                <div class="card h-100 shadow-sm border-0 p-4 bg-white d-flex flex-column justify-content-between" style="border-radius: 0.75rem; border-top: 4px solid #00a3e0 !important;">
                    <div>
                        <span class="text-uppercase fw-bold text-muted tracking-wider" style="font-size: 0.75rem; letter-spacing: 1px;">Lite Entry</span>
                        <h3 class="h3 fw-bold text-dark my-2">Developer Pro</h3>
                        <p class="text-muted small mb-4">For a single small website, a staging copy or a lightweight custom PHP site.</p>
                        <div class="mb-4">
                            <span class="display-5 fw-bold text-dark">₹499</span>
                            <span class="text-muted">/ month</span>
                        </div>
                        <hr class="text-slate-200">
                        <ul class="list-unstyled my-4 text-muted small">
                            <li class="mb-2">⚡ <strong>10 GB</strong> NVMe SSD storage</li>
                            <li class="mb-2">🌐 <strong>1</strong> hosted domain</li>
                            <li class="mb-2">🔒 Free Let's Encrypt SSL certificates</li>
                            <li class="mb-2">📧 5 email inboxes on your domain</li>
                        </ul>
                    </div>
                    <?= Html::a('Enquire about Developer Pro', ['site/contact', 'subject' => 'Hosting enquiry: Developer Pro'], ['class' => 'btn btn-outline-dark w-100 fw-bold py-2', 'style' => 'border-radius: 0.5rem;']) ?>
                </div>
            </div>

            <!-- Plan 2 -->
            <div class="col-lg-4">
                <div class="card h-100 shadow-lg border-0 p-4 text-white d-flex flex-column justify-content-between position-relative overflow-hidden" 
                     style="border-radius: 0.75rem; border: 2px solid #dc2626 !important; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;">
                    <div class="position-absolute top-0 end-0 text-white px-3 py-1 small fw-bold" style="border-bottom-left-radius: 0.5rem; font-size: 0.75rem; text-transform: uppercase; background-color: #dc2626;">
                        Recommended Plan
                    </div>
                    <div>
                        <span class="text-uppercase fw-bold tracking-wider" style="font-size: 0.75rem; letter-spacing: 1px; color: #00a3e0 !important;">Business</span>
                        <h3 class="h3 fw-bold text-white my-2">Business Matrix</h3>
                                                <p class="small mb-4" style="color: #cbd5e1 !important;">For busy business portals, online shops and analytics systems.</p>
                        <div class="mb-4">
                            <span class="display-5 fw-bold text-white">₹1,499</span>
                            <span class="text-slate-300" style="color: #cbd5e1 !important;">/ month</span>
                        </div>
                        <hr style="border-color: rgba(255,255,255,0.15);">
                        <ul class="list-unstyled my-4 small" style="color: #e2e8f0 !important;">
                            <li class="mb-2">⚡ <strong>50 GB</strong> NVMe storage</li>
                            <li class="mb-2">🌐 <strong>5</strong> hosted domains</li>
                            <li class="mb-2">🗄️ <strong>Unlimited</strong> MySQL databases</li>
                            <li class="mb-2">⏰ <strong>Automated daily backups</strong></li>
                        </ul>
                    </div>
                    <?= Html::a('Enquire about Business Matrix', ['site/contact', 'subject' => 'Hosting enquiry: Business Matrix'], [
                        'class' => 'btn w-100 fw-bold py-3 text-white',
                        'style' => 'background-color: #00a3e0 !important; border: none !important; border-radius: 0.5rem; color: #ffffff !important;'
                    ]) ?>
                </div>
            </div>

            <!-- Plan 3 -->
            <div class="col-lg-4">
                <div class="card h-100 shadow-sm border-0 p-4 bg-white d-flex flex-column justify-content-between" style="border-radius: 0.75rem; border-top: 4px solid #dc2626 !important;">
                    <div>
                        <span class="text-uppercase fw-bold text-muted tracking-wider" style="font-size: 0.75rem; letter-spacing: 1px;">Agency</span>
                        <h3 class="h3 fw-bold text-dark my-2">Agency Premium</h3>
                        <p class="text-muted small mb-4">For agencies and larger clients running many websites.</p>
                        <div class="mb-4">
                            <span class="display-5 fw-bold text-dark">₹3,499</span>
                            <span class="text-muted">/ month</span>
                        </div>
                        <hr class="text-slate-200">
                        <ul class="list-unstyled my-4 text-muted small">
                            <li class="mb-2">⚡ <strong>150 GB</strong> NVMe storage</li>
                            <li class="mb-2">🌐 <strong>Unlimited</strong> hosted domains</li>
                            <li class="mb-2">🛡️ Firewall protection</li>
                            <li class="mb-2">📞 Priority support</li>
                        </ul>
                    </div>
                    <?= Html::a('Enquire about Agency Premium', ['site/contact', 'subject' => 'Hosting enquiry: Agency Premium'], [
                        'class' => 'btn w-100 fw-bold py-2',
                        'style' => 'border: 2px solid #dc2626 !important; color: #dc2626 !important; background: transparent !important; border-radius: 0.5rem;'
                    ]) ?>
                </div>
            </div>
        </div>

        <!-- How accounts are run -->
        <div class="mt-5 p-5 rounded-4 text-center border bg-white shadow-sm" style="border-radius: 0.75rem;">
            <h4 class="fw-bold text-dark mb-2 text-uppercase tracking-wider fs-6" style="letter-spacing: 0.5px;">Run for You</h4>
            <p class="text-muted mx-auto mb-3" style="max-width: 700px;">
                Every account runs on cPanel, and our team handles renewals, invoices and support through your customer portal.
            </p>
            <div class="d-flex justify-content-center gap-4 align-items-center text-muted fw-semibold flex-wrap small">
                <div style="color: #00a3e0;">✓ cPanel Hosting</div>
                <div style="color: #dc2626;">✓ Free SSL Certificates</div>
                <div style="color: #00a3e0;">✓ Customer Portal</div>
            </div>
        </div>
    </div>
</div>
