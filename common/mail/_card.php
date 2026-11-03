<?php

declare(strict_types=1);

use yii\helpers\Html;

/**
 * The standard email body: one bordered card inside layouts/html.php (which adds the
 * logo header and footer). Every HTML email renders this, so they all look the same.
 *
 * @var yii\web\View $this
 * @var string $tone success | info | warning | danger (border, icon and note colour)
 * @var string|null $icon e.g. '✓', '!'; omitted = no icon
 * @var string|null $eyebrow small uppercase label above the title
 * @var string $title
 * @var string|null $intro paragraph under the title (plain text)
 * @var array|null $rows [label, value, 'accent'?] rows of the details box (plain text); 'accent' colours the value
 * @var string|null $preformatted plain text shown as-is (line breaks kept), e.g. admin alerts
 * @var array|null $note [text, tone] highlighted message; tone defaults to the card's
 * @var array|null $button [label, url]
 * @var string|null $footnote small paragraph under the button (plain text)
 */
$tones = [
    'success' => ['border' => '#22c55e', 'accent' => '#16a34a', 'noteBg' => '#ecfdf5', 'noteText' => '#166534'],
    'info' => ['border' => '#2563eb', 'accent' => '#2563eb', 'noteBg' => '#eff6ff', 'noteText' => '#1e40af'],
    'warning' => ['border' => '#f97316', 'accent' => '#ea580c', 'noteBg' => '#fff7ed', 'noteText' => '#9a3412'],
    'danger' => ['border' => '#ef4444', 'accent' => '#dc2626', 'noteBg' => '#fef2f2', 'noteText' => '#991b1b'],
];
$t = $tones[$tone] ?? $tones['info'];
$rows ??= [];
?>
<div class="email-card" style="background:#ffffff; border:1px solid <?= $t['border'] ?>; border-radius:18px; padding:38px 40px; color:#171717; font-family:Arial,Helvetica,sans-serif;">
<?php if (!empty($icon)): ?>
    <div style="font-size:44px; line-height:1; color:<?= $t['accent'] ?>; margin-bottom:20px;"><?= Html::encode($icon) ?></div>
<?php endif; ?>
<?php if (!empty($eyebrow)): ?>
    <div style="color:<?= $t['accent'] ?>; font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; margin-bottom:12px;"><?= Html::encode($eyebrow) ?></div>
<?php endif; ?>
    <h1 style="margin:0 0 14px; font-size:28px; line-height:1.25; color:#171717;"><?= Html::encode($title) ?></h1>
<?php if (!empty($intro)): ?>
    <p style="margin:0 0 26px; font-size:16px; line-height:1.7; color:#4b5563;"><?= Html::encode($intro) ?></p>
<?php endif; ?>
<?php if ($rows !== []): ?>
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#f9fafb; border-radius:12px; margin-bottom:28px;">
    <?php foreach (array_values($rows) as $i => $row): ?>
        <tr>
            <td style="padding:<?= $i === 0 ? '18px 18px 14px' : '0 18px 14px' ?>; font-size:14px; color:#4b5563;"><?= Html::encode($row[0]) ?></td>
            <td align="right" style="padding:<?= $i === 0 ? '18px 18px 14px' : '0 18px 14px' ?>; font-size:14px; font-weight:700; color:<?= ($row[2] ?? null) === 'accent' ? $t['accent'] : '#171717' ?>;"><?= Html::encode((string) $row[1]) ?></td>
        </tr>
    <?php endforeach; ?>
        <tr><td colspan="2" style="height:4px; line-height:4px; font-size:0;">&nbsp;</td></tr>
    </table>
<?php endif; ?>
<?php if (!empty($preformatted)): ?>
    <div style="background:#f9fafb; border-radius:12px; padding:18px; margin-bottom:<?= empty($note) && empty($button) ? '0' : '28px' ?>; font-size:14px; line-height:1.7; color:#374151;"><?= nl2br(Html::encode($preformatted)) ?></div>
<?php endif; ?>
<?php if (!empty($note)): ?>
    <?php $n = $tones[$note[1] ?? $tone] ?? $t; ?>
    <div style="background:<?= $n['noteBg'] ?>; border-left:4px solid <?= $n['border'] ?>; padding:14px 16px; margin-bottom:26px; color:<?= $n['noteText'] ?>; font-size:14px; line-height:1.6;"><?= Html::encode($note[0]) ?></div>
<?php endif; ?>
<?php if (!empty($button)): ?>
    <a class="button" href="<?= Html::encode($button[1]) ?>" style="display:inline-block; background:#171717; color:#ffffff; text-decoration:none; font-size:15px; font-weight:700; padding:16px 25px; border-radius:7px;"><?= Html::encode($button[0]) ?> &rarr;</a>
<?php endif; ?>
<?php if (!empty($footnote)): ?>
    <p style="margin:24px 0 0; font-size:13px; line-height:1.6; color:#6b7280;"><?= Html::encode($footnote) ?></p>
<?php endif; ?>
</div>
