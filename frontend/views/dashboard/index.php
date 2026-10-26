<?php
declare(strict_types=1);

use common\models\Invoice;
use yii\helpers\Html;

$this->title = 'Customer Portal';
$today = new \DateTimeImmutable('today');
?>

<div class="container py-4">
    <div class="card shadow-sm mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Upcoming Renewals</strong>
            <?= Html::a('View All Services', ['/service/index'], ['class' => 'btn btn-sm btn-outline-primary']) ?>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Type</th>
                        <th>Expires</th>
                        <th>Remaining</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($upcomingServices as $service): ?>
                        <?php
                        $expiry = new \DateTimeImmutable($service['expiry']);
                        $days = (int) $today->diff($expiry)->format('%r%a');
                        ?>
                        <tr>

                            <td class="fw-semibold">
                                <?= Html::encode($service['name']) ?>
                            </td>

                            <td>
                                <?= Html::encode(ucfirst($service['type'])) ?>
                            </td>

                            <td>
                                <?= Yii::$app->formatter->asDate($service['expiry'], 'php:d M Y') ?>
                            </td>

                            <td>
                                <?php if ($days <= 5): ?>
                                    <span class="badge bg-danger"><?= $days ?> day(s)</span>
                                <?php elseif ($days <= 15): ?>
                                    <span class="badge bg-warning text-dark"><?= $days ?> day(s)</span>
                                <?php else: ?>
                                    <span class="badge bg-info"><?= $days ?> day(s)</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?= Html::a('View', ['/service/view', 'type' => $service['type'], 'id' => $service['id']], ['class' => 'btn btn-sm btn-outline-primary']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$upcomingServices): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                No services expire within the next 60 days.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mb-4">
        <h2 class="mb-1">
            Welcome, <?= Html::encode(Yii::$app->user->identity->username) ?>
        </h2>
        <div class="text-muted">Manage your invoices and payments.</div>
    </div>

    <div class="row g-3 mb-4">

        <div class="col-md-3">

            <div class="card shadow-sm h-100">

                <div class="card-body">

                    <div class="text-muted">
                        Total Invoices
                    </div>

                    <div class="fs-3 fw-bold">
                        <?= $totalInvoices ?>
                    </div>

                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="card shadow-sm h-100">

                <div class="card-body">

                    <div class="text-muted">
                        Open Invoices
                    </div>

                    <div class="fs-3 fw-bold text-danger">
                        <?= $openInvoices ?>
                    </div>

                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="card shadow-sm h-100">

                <div class="card-body">

                    <div class="text-muted">
                        Outstanding
                    </div>

                    <div class="fs-4 fw-bold">
                        <?=
                                Yii::$app
                                ->formatter
                                ->asCurrency(
                                        $outstandingTotal,
                                        'INR'
                                )
                        ?>
                    </div>

                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="card shadow-sm h-100">

                <div class="card-body">

                    <div class="text-muted">
                        Payments Under Review
                    </div>

                    <div class="fs-3 fw-bold text-warning">
                        <?= $pendingPayments ?>
                    </div>

                </div>

            </div>

        </div>

    </div>

    <div class="card shadow-sm">

        <div class="card-header d-flex justify-content-between align-items-center">

            <strong>
                Recent Invoices
            </strong>

            <?=
            Html::a(
                    'View All Invoices',
                    ['/invoice/index'],
                    [
                        'class' =>
                        'btn btn-sm btn-primary',
                    ]
            )
            ?>

        </div>

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">

                        <tr>
                            <th>
                                Invoice
                            </th>

                            <th>
                                Issued
                            </th>

                            <th>
                                Due
                            </th>

                            <th class="text-end">
                                Amount
                            </th>

                            <th>
                                Status
                            </th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php
                        foreach (
                                $recentInvoices as $invoice
                        ):
                            ?>

                            <tr>

                                <td>
                                    <?=
                                    Html::a(
                                            Html::encode(
                                                    $invoice
                                                    ->invoice_number
                                            ),
                                            [
                                                '/invoice/view',
                                                'number' =>
                                                $invoice
                                                ->invoice_number,
                                            ]
                                    )
                                    ?>
                                </td>

                                <td>
                                    <?=
                                            Yii::$app
                                            ->formatter
                                            ->asDate(
                                                    $invoice
                                                    ->created_at,
                                                    'php:d M Y'
                                            )
                                    ?>
                                </td>

                                <td>
                                    <?=
                                    $invoice->due_date ? Yii::$app
                                                    ->formatter
                                                    ->asDate(
                                                            $invoice
                                                            ->due_date,
                                                            'php:d M Y'
                                                    ) : '-'
                                    ?>
                                </td>

                                <td class="text-end">
                                    <?=
                                            Yii::$app
                                            ->formatter
                                            ->asCurrency(
                                                    $invoice
                                                    ->total_payable,
                                                    'INR'
                                            )
                                    ?>
                                </td>

                                <td>
                                    <?=
                                    Html::encode(
                                            Invoice::getStatuses()[
                                            $invoice->status
                                            ] ?? $invoice->status
                                    )
                                    ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                        <?php
                        if (
                                !$recentInvoices
                        ):
                            ?>

                            <tr>
                                <td
                                    colspan="5"
                                    class="text-center text-muted py-4"
                                    >
                                    No invoices available.
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    <div class="d-flex flex-wrap gap-2 mb-4">
        <?= Html::a('My Invoices', ['/invoice/index'], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('My Profile', ['/account/profile'], ['class' => 'btn btn-outline-primary']) ?>
        <?= Html::a('Change Password', ['/account/change-password'], ['class' => 'btn btn-outline-secondary']) ?>
    </div>

</div>