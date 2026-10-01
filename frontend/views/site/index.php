<?php

/** @var yii\web\View $this */

use yii\bootstrap5\Html;

$this->title = 'Miracle Web Technologies | Enterprise Infrastructure & Premium Cloud Hosting';
?>
<div class="site-index" style="background-color: #f8fafc; color: #1e293b; font-family: 'Inter', system-ui, sans-serif;">

    <!-- 🚀 HERO BLOCK -->
    <div class="jumbotron text-center py-5 px-3 mb-5 border-0 position-relative shadow-sm" 
         style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-radius: 1rem; color: #ffffff;">
        <div class="container py-4">
            <span class="badge px-3 py-2 mb-3 text-uppercase fw-bold shadow-sm" style="letter-spacing: 1.5px; font-size: 0.8rem; background-color: #dc2626;">
                ⚡ Premium Reseller Infrastructure Node Active
            </span>
            <h1 class="display-3 fw-bold mb-3 tracking-tight" style="color: #ffffff;">
                Modern Systems. <span style="color: #00a3e0;">Absolute Stability.</span>
            </h1>
            <p class="lead mx-auto mb-4 text-slate-300" style="max-width: 800px; color: #cbd5e1; font-weight: 300; font-size: 1.25rem;">
                We migrate legacy architectures to high-performance local container networks and provide premium web hosting packages backed by a 99.9% uptime SLA matrix.
            </p>
            <div class="d-flex justify-content-center gap-3 flex-wrap pt-2">
                <!-- 💡 FIXED BUTTON CONTRAST: Forced crisp white text over your brand cyan background -->
                <?= Html::a('Request Systems Audit ⚡', ['site/contact'], [
                    'class' => 'btn btn-lg px-4 py-3 fw-bold shadow-sm text-white', 
                    'style' => 'background-color: #00a3e0 !important; border: none !important; min-width: 240px; border-radius: 0.5rem; color: #ffffff !important;'
                ]) ?>
                <a class="btn btn-outline-light btn-lg px-4 py-3 fw-bold" href="#hosting-tiers" style="border-color: #475569; color: #f1f5f9; min-width: 240px; border-radius: 0.5rem;">
                    Explore Brand Hosting Tiers
                </a>
            </div>
        </div>
    </div>

    <!-- 💰 HOSTING PLANS UPSALE GRID -->
    <div id="hosting-tiers" class="container py-4">
        <div class="text-center mb-5">
            <h2 class="fw-bold text-dark h1 mb-2">High-Performance Managed Hosting</h2>
            <p class="text-muted fs-5 mx-auto" style="max-width: 600px;">
                Commercial configurations optimized for high-traffic custom legacy applications, corporate domains, and web agencies.
            </p>
        </div>

        <div class="row g-4 align-items-stretch">
            <!-- Plan 1: Developer Core -->
            <div class="col-lg-4">
                <div class="card h-100 shadow-sm border-0 p-4 bg-white d-flex flex-column justify-content-between" style="border-radius: 0.75rem; border-top: 4px solid #00a3e0 !important;">
                    <div>
                        <span class="text-uppercase fw-bold text-muted tracking-wider" style="font-size: 0.75rem; letter-spacing: 1px;">Lite Entry</span>
                        <h3 class="h3 fw-bold text-dark my-2">Developer Pro</h3>
                        <p class="text-muted small mb-4">Perfect for staging configurations, customer concept previews, and lightweight custom PHP setups.</p>
                        <div class="mb-4">
                            <span class="display-5 fw-bold text-dark">₹499</span>
                            <span class="text-muted">/ month</span>
                        </div>
                        <hr class="text-slate-200">
                        <ul class="list-unstyled my-4 text-muted small">
                            <li class="mb-2">⚡ <strong>10 GB</strong> NVMe SSD Storage Arrays</li>
                            <li class="mb-2">🌐 <strong>1</strong> Hosted Domain Limit</li>
                            <li class="mb-2">🔒 Free Let's Encrypt SSL Security Certificates</li>
                            <li class="mb-2">📧 5 Corporate Email Domain Inboxes</li>
                        </ul>
                    </div>
                    <?= Html::a('Deploy Developer Core', ['site/contact'], ['class' => 'btn btn-outline-dark w-100 fw-bold py-2', 'style' => 'border-radius: 0.5rem;']) ?>
                </div>
            </div>

            <!-- Plan 2: Business Scale (💡 FIXED BOX CONTRAST LOOP) -->
            <div class="col-lg-4">
                <div class="card h-100 shadow-lg border-0 p-4 text-white d-flex flex-column justify-content-between position-relative overflow-hidden" 
                     style="border-radius: 0.75rem; border: 2px solid #dc2626 !important; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;">
                    <div class="position-absolute top-0 end-0 text-white px-3 py-1 small fw-bold" style="border-bottom-left-radius: 0.5rem; font-size: 0.75rem; text-transform: uppercase; background-color: #dc2626;">
                        Recommended Plan
                    </div>
                    <div>
                        <span class="text-uppercase fw-bold tracking-wider" style="font-size: 0.75rem; letter-spacing: 1px; color: #00a3e0 !important;">Enterprise Stack</span>
                        <h3 class="h3 fw-bold text-white my-2">Business Matrix</h3>
                        <!-- 💡 Forced clear visibility text rules for dark cards -->
                        <p class="small mb-4" style="color: #cbd5e1 !important;">Tailored specifically to run intensive Yii Advanced portals, e-commerce modules, and analytics systems.</p>
                        <div class="mb-4">
                            <span class="display-5 fw-bold text-white">₹1,499</span>
                            <span class="text-slate-300" style="color: #cbd5e1 !important;">/ month</span>
                        </div>
                        <hr style="border-color: rgba(255,255,255,0.15);">
                        <ul class="list-unstyled my-4 small" style="color: #e2e8f0 !important;">
                            <li class="mb-2">⚡ <strong>50 GB</strong> High-Performance NVMe Space</li>
                            <li class="mb-2">🌐 <strong>5</strong> Fully Masked Routed Domains</li>
                            <li class="mb-2">🗄️ <strong>Unlimited</strong> MySQL Database Allocations</li>
                            <li class="mb-2">⏰ <strong>Automated Daily Backups</strong> Offsite Script</li>
                        </ul>
                    </div>
                    <?= Html::a('Deploy Business Matrix 🚀', ['site/contact'], [
                        'class' => 'btn w-100 fw-bold py-3 text-white', 
                        'style' => 'background-color: #00a3e0 !important; border: none !important; border-radius: 0.5rem; color: #ffffff !important;'
                    ]) ?>
                </div>
            </div>

            <!-- Plan 3: Agency Supreme -->
            <div class="col-lg-4">
                <div class="card h-100 shadow-sm border-0 p-4 bg-white d-flex flex-column justify-content-between" style="border-radius: 0.75rem; border-top: 4px solid #dc2626 !important;">
                    <div>
                        <span class="text-uppercase fw-bold text-muted tracking-wider" style="font-size: 0.75rem; letter-spacing: 1px;">Maximum Power</span>
                        <h3 class="h3 fw-bold text-dark my-2">Agency Premium</h3>
                        <p class="text-muted small mb-4">Dedicated operational limits for large multi-tenant corporate clients and fast deployment arrays.</p>
                        <div class="mb-4">
                            <span class="display-5 fw-bold text-dark">₹3,499</span>
                            <span class="text-muted">/ month</span>
                        </div>
                        <hr class="text-slate-200">
                        <ul class="list-unstyled my-4 text-muted small">
                            <li class="mb-2">⚡ <strong>150 GB</strong> Unrestricted NVMe Drives</li>
                            <li class="mb-2">🌐 <strong>Unlimited</strong> External Top-Tier Domain Links</li>
                            <li class="mb-2">🛡️ Full Injection Firewall Shields Engaged</li>
                            <li class="mb-2">📞 24/7 Priority Architectural Support SLA</li>
                        </ul>
                    </div>
                    <?= Html::a('Deploy Corporate Cloud', ['site/contact'], [
                        'class' => 'btn w-100 fw-bold py-2', 
                        'style' => 'border: 2px solid #dc2626 !important; color: #dc2626 !important; background: transparent !important; border-radius: 0.5rem;'
                    ]) ?>
                </div>
            </div>
        </div>

        <!-- 🏢 PROOF OF COMPETENCY PANEL -->
        <div class="mt-5 p-5 rounded-4 text-center border bg-white shadow-sm" style="border-radius: 0.75rem;">
            <h4 class="fw-bold text-dark mb-2 text-uppercase tracking-wider fs-6" style="letter-spacing: 0.5px;">Operational Resilient Infrastructure</h4>
            <p class="text-muted mx-auto mb-3" style="max-width: 700px;">
                Every provisioned account maps cleanly through secure cPanel node virtualization, executing automated multi-stage environmental security loops to seal databases from script vulnerabilities.
            </p>
            <div class="d-flex justify-content-center gap-4 align-items-center text-muted fw-semibold flex-wrap small">
                <div style="color: #00a3e0;">✓ Apache Pretty URLs Enabled</div>
                <div style="color: #dc2626;">✓ Secure Environment Shield Active</div>
                <div style="color: #00a3e0;">✓ Unified Shared DB Matrix Connected</div>
            </div>
        </div>
    </div>
</div>
