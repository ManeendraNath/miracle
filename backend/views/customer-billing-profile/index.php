<?php

declare(strict_types=1);

use common\helpers\GridActionHelper;
use common\helpers\AdminIndexHelper;
use yii\helpers\Html;

$this->title = 'Customer Billing Profiles';

$this->params['breadcrumbs'][] = $this->title;
?>

<div class="card card-outline card-primary shadow-sm">
    <?= AdminIndexHelper::pageHeader(
        'Legal billing information used when invoices are generated.',
        'create',
        'Add Billing Profile',
    ) ?>

    <div class="card-body p-0">

        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead class="table-light">
                    <tr>
                        <th>Client</th>
                        <th>Legal Name</th>
                        <th>State / Country</th>
                        <th>GSTIN</th>
                        <th>PAN</th>
                        <th class="text-end">
                            Action
                        </th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($profiles as $profile): ?>
                        <tr>

                            <td>
                                <?= Html::encode($profile->user?->username ?? '-') ?>
                            </td>

                            <td>
                                <?= Html::encode($profile->legal_name) ?>
                            </td>

                            <td>
                                <?= Html::encode($profile->isForeign() ? $profile->country : $profile->state) ?>
                            </td>

                            <td>
                                <?= Html::encode($profile->gstin ?: '-') ?>
                            </td>

                            <td>
                                <?= Html::encode($profile->pan ?: '-') ?>
                            </td>

                            <td class="text-center text-nowrap">
                                <?= GridActionHelper::button('update', ['update', 'id' => $profile->id]) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    <?php if (!$profiles): ?>
                        <tr>
                            <td
                                colspan="6"
                                class="text-center text-muted py-4"
                                >
                                No billing profiles found.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>
