<?php

use common\components\CustomYii;
use yii\bootstrap5\Html;
?>
<!-- 🚀 DYNAMIC CORRESPONDING BRAND FOOTER -->
<footer class="footer mt-auto py-5 text-white" style="background-color: #0f172a; border-top: 4px solid #00a3e0;">
    <div class="container">
        <div class="row g-4">

            <!-- Column 1: Agency Brand Statement with Embedded Logo -->
<div class="col-lg-4 mb-3">
    <div class="d-flex align-items-center mb-3">
        <img src="<?= Yii::$app->request->baseUrl ?>/favicon.ico" alt="Logo" style="width:32px; height:32px; margin-right:12px; object-fit:contain;">
        <h5 class="fw-bold m-0 text-white"><?= Html::encode(CustomYii::getSetting('site_name', 'Miracle Web Technologies')) ?></h5>
    </div>
    <p class="small text-muted" style="color: #94a3b8 !important; line-height: 1.6;">
        Enterprise level modernization frameworks, legacy code migrations, local network container routing orchestration, and isolated reseller cloud hosting instances.
    </p>
</div>


            <!-- Column 2: Communications Matrix Realignment -->
            <div class="col-lg-4 mb-3">
                <h5 class="fw-bold mb-3" style="color: #00a3e0;">📞 Core Communication Nodes</h5>
                <ul class="list-unstyled small text-muted p-0 m-0">
                    <li class="mb-2" style="color: #cbd5e1 !important;">
                        <strong>Support Hotline:</strong> +<?= Html::encode(CustomYii::getSetting('mobile_1', '919999999999')) ?>
                    </li>
<?php if (CustomYii::getSetting('mobile_2')): ?>
                        <li class="mb-2" style="color: #cbd5e1 !important;">
                            <strong>Alternative Line:</strong> +<?= Html::encode(CustomYii::getSetting('mobile_2')) ?>
                        </li>
<?php endif; ?>
                    <li class="mb-2" style="color: #cbd5e1 !important;">
                        <strong>Primary Desk Mail:</strong> <a href="mailto:<?= Html::encode(CustomYii::getSetting('email_1')) ?>" style="color: #00a3e0; text-decoration: none;"><?= Html::encode(CustomYii::getSetting('email_1', 'info@miraclewebtechnologies.com')) ?></a>
                    </li>
                </ul>
            </div>

            <!-- Column 3: Social Network Vault Vectors -->
            <div class="col-lg-4 mb-3">
                <h5 class="fw-bold mb-3" style="color: #dc2626;">🌐 Social Infrastructure Channels</h5>
                <div class="d-flex gap-3 flex-wrap small">
                    <?php if (CustomYii::getSetting('facebook')): ?>
                        <a href="<?= Html::encode(CustomYii::getSetting('facebook')) ?>" target="_blank" rel="noopener" class="text-white bg-secondary px-3 py-1 rounded text-decoration-none" style="background-color: #1e293b !important;">Facebook</a>
                    <?php endif; ?>
                    <?php if (CustomYii::getSetting('x')): ?>
                        <a href="<?= Html::encode(CustomYii::getSetting('x')) ?>" target="_blank" rel="noopener" class="text-white bg-secondary px-3 py-1 rounded text-decoration-none" style="background-color: #1e293b !important;">Twitter / X</a>
                    <?php endif; ?>
                    <?php if (CustomYii::getSetting('linkedin')): ?>
                        <a href="<?= Html::encode(CustomYii::getSetting('linkedin')) ?>" target="_blank" rel="noopener" class="text-white bg-secondary px-3 py-1 rounded text-decoration-none" style="background-color: #1e293b !important;">LinkedIn</a>
                    <?php endif; ?>
                    <?php if (CustomYii::getSetting('instagram')): ?>
                        <a href="<?= Html::encode(CustomYii::getSetting('instagram')) ?>" target="_blank" rel="noopener" class="text-white bg-secondary px-3 py-1 rounded text-decoration-none" style="background-color: #1e293b !important;">Instagram</a>
<?php endif; ?>
                </div>
                <div class="mt-3 small text-muted" style="color: #64748b !important;">
                    🏢 Head Office: <?= Html::encode(CustomYii::getSetting('address_line_1', 'Tech Suite Hub Area')) ?>
                </div>
            </div>

        </div>

        <hr class="mt-4 mb-3" style="border-color: rgba(255,255,255,0.1);">
        <div class="d-flex justify-content-between flex-wrap text-muted small" style="color: #64748b !important;">
            <div>&copy; <?= date('Y') ?> <?= Html::encode(CustomYii::getSetting('site_name')) ?>. All Privileges Reserved.</div>
            <div>Infrastructure Node: <span class="text-success">● Balanced Operational Sync</span></div>
        </div>
    </div>
</footer>
