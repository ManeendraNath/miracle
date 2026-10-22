<?php
declare(strict_types=1);

use yii\helpers\Html;

$this->title = 'My Services';

$this->params['breadcrumbs'][] = $this->title;

$statusBadge = static function (
        int|string|null $status
): string {
    $active = (int) $status === 1;

    return Html::tag(
            'span',
            $active ? 'Active' : 'Inactive',
            [
                'class' =>
                $active ? 'badge bg-success' : 'badge bg-secondary',
            ]
    );
};
?>

<div class="container py-4">

    <div class="mb-4">

        <h2 class="mb-1">
            My Services
        </h2>

        <div class="text-muted">
            View your domains, hosting and maintenance services.
        </div>

    </div>

    <div class="card shadow-sm mb-4">

        <div class="card-header">
            <strong>
                Domains
            </strong>
        </div>

        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead>
                    <tr>
                        <th>Domain</th>
                        <th>Expiry</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>

                    <?php
                    foreach (
                            $domains as $domain
                    ):
                        ?>

                        <tr>

                            <td class="fw-semibold">
                                <?=
                                Html::encode(
                                        $domain->domain_url
                                )
                                ?>
                            </td>

                            <td>
                                <?=
                                $domain->current_expiry_date ? Yii::$app
                                                ->formatter
                                                ->asDate(
                                                        $domain->current_expiry_date,
                                                        'php:d M Y'
                                                ) : '-'
                                ?>
                            </td>

                            <td>
                                <?=
                                $statusBadge(
                                        $domain->status
                                )
                                ?>
                            </td>

                            <td class="text-end">

                                <?=
                                Html::a(
                                        'View',
                                        [
                                            'view',
                                            'type' =>
                                            'domain',
                                            'id' =>
                                            $domain->id,
                                        ],
                                        [
                                            'class' =>
                                            'btn btn-sm '
                                            . 'btn-outline-primary',
                                        ]
                                )
                                ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    <?php if (!$domains): ?>

                        <tr>
                            <td
                                colspan="4"
                                class="text-center text-muted py-4"
                                >
                                No domains are currently assigned.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

    <div class="card shadow-sm mb-4">

        <div class="card-header">
            <strong>
                Hosting
            </strong>
        </div>

        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead>
                    <tr>
                        <th>Primary Domain</th>
                        <th>Expiry</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>

                    <?php
                    foreach (
                            $hosting as $host
                    ):
                        ?>

                        <tr>

                            <td class="fw-semibold">
                                <?=
                                Html::encode(
                                        $host->primary_domain
                                )
                                ?>
                            </td>

                            <td>
                                <?=
                                $host->current_expiry_date ? Yii::$app
                                                ->formatter
                                                ->asDate(
                                                        $host->current_expiry_date,
                                                        'php:d M Y'
                                                ) : '-'
                                ?>
                            </td>

                            <td>
                                <?=
                                $statusBadge(
                                        $host->status
                                )
                                ?>
                            </td>

                            <td class="text-end">

                                <?=
                                Html::a(
                                        'View',
                                        [
                                            'view',
                                            'type' =>
                                            'hosting',
                                            'id' =>
                                            $host->id,
                                        ],
                                        [
                                            'class' =>
                                            'btn btn-sm '
                                            . 'btn-outline-primary',
                                        ]
                                )
                                ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    <?php if (!$hosting): ?>

                        <tr>
                            <td
                                colspan="4"
                                class="text-center text-muted py-4"
                                >
                                No hosting services are currently assigned.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>
    <div class="card shadow-sm">
        <div class="card-header">
            <strong>
                Maintenance
            </strong>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Website</th>
                        <th>Expiry</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach (
                            $maintenance as $item
                    ):
                        ?>
                        <tr>
                            <td class="fw-semibold">
                                <?=
                                Html::encode(
                                        $item->website_url
                                )
                                ?>
                            </td>
                            <td>
                                <?=
                                $item->current_expiry_date ? Yii::$app
                                                ->formatter
                                                ->asDate(
                                                        $item->current_expiry_date,
                                                        'php:d M Y'
                                                ) : '-'
                                ?>
                            </td>
                            <td>
                                <?=
                                $statusBadge(
                                        $item->status
                                )
                                ?>
                            </td>
                            <td class="text-end">
                                <?=
                                Html::a(
                                        'View',
                                        [
                                            'view',
                                            'type' =>
                                            'maintenance',
                                            'id' =>
                                            $item->id,
                                        ],
                                        [
                                            'class' =>
                                            'btn btn-sm '
                                            . 'btn-outline-primary',
                                        ]
                                )
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$maintenance): ?>
                        <tr>
                            <td
                                colspan="4"
                                class="text-center text-muted py-4"
                                >
                                No maintenance services are currently assigned.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>