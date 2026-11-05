<?php

declare(strict_types=1);

use yii\helpers\Html;

$this->title = 'My Services';

$this->params['breadcrumbs'][] = $this->title;

$formatter = Yii::$app->formatter;
$today = date('Y-m-d');
$statusBadge = static fn($row): string => (int) $row->status === 1
    ? '<span class="badge bg-success-subtle text-success-emphasis">Active</span>'
    : '<span class="badge bg-secondary-subtle text-secondary-emphasis">' . Html::encode($row::getStatuses()[(int) $row->status] ?? 'Inactive') . '</span>';
// Past expiry on a still-active service is shown in red.
$expiry = static function ($row) use ($formatter, $today): string {
    if (!$row->current_expiry_date) {
        return '-';
    }
    $date = $formatter->asDate($row->current_expiry_date, 'php:d M Y');
    return (int) $row->status === 1 && $row->current_expiry_date < $today ? Html::tag('span', $date, ['class' => 'text-danger fw-semibold']) : $date;
};

$sections = [
    ['title' => 'Domains', 'icon' => 'fa-globe', 'column' => 'Domain', 'type' => 'domain', 'attribute' => 'domain_url',
        'rows' => $domains, 'empty' => 'No domains are currently assigned.'],
    ['title' => 'Hosting', 'icon' => 'fa-server', 'column' => 'Primary Domain', 'type' => 'hosting', 'attribute' => 'primary_domain',
        'rows' => $hosting, 'empty' => 'No hosting services are currently assigned.'],
    ['title' => 'Maintenance', 'icon' => 'fa-tools', 'column' => 'Website', 'type' => 'maintenance', 'attribute' => 'website_url',
        'rows' => $maintenance, 'empty' => 'No maintenance services are currently assigned.'],
];
?>

<div class="mb-4">
    <h1 class="h3 fw-bold mb-1">My Services</h1>
    <div class="text-body-secondary">View your domains, hosting and maintenance services.</div>
</div>

<?php foreach ($sections as $section): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent d-flex align-items-center gap-2 py-3">
            <i class="fas <?= $section['icon'] ?> fa-fw text-primary"></i>
            <h2 class="h6 fw-bold mb-0"><?= $section['title'] ?></h2>
            <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis"><?= count($section['rows']) ?></span>
        </div>

        <?php if (!$section['rows']): ?>
            <div class="text-center py-5 text-body-secondary">
                <i class="fas <?= $section['icon'] ?> fa-2x mb-2 opacity-50"></i>
                <div><?= $section['empty'] ?></div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="small text-body-secondary">
                        <tr>
                            <th class="ps-3"><?= $section['column'] ?></th>
                            <th class="d-none d-md-table-cell">Expiry</th>
                            <th class="d-none d-md-table-cell">Status</th>
                            <th class="pe-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($section['rows'] as $row): ?>
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-semibold text-break"><?= Html::encode($row->{$section['attribute']}) ?></div>
                                    <div class="d-md-none small d-flex flex-wrap align-items-center gap-2 mt-1"><?= $statusBadge($row) ?><span class="text-body-secondary">Expires <?= $expiry($row) ?></span></div>
                                </td>
                                <td class="text-nowrap d-none d-md-table-cell"><?= $expiry($row) ?></td>
                                <td class="d-none d-md-table-cell"><?= $statusBadge($row) ?></td>
                                <td class="text-end pe-3">
                                    <?= Html::a('View', ['view', 'type' => $section['type'], 'id' => $row->id], ['class' => 'btn btn-sm btn-outline-primary']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
