<?php
declare(strict_types=1);

use yii\helpers\Html;

$this->title = 'Customer Billing Profiles';

$this->params['breadcrumbs'][] = $this->title;
?>

<div class="d-flex justify-content-between align-items-center mb-3">

    <div>
        <h4 class="mb-1">
            Customer Billing Profiles
        </h4>

        <div class="text-muted">
            Legal billing information used when invoices are generated.
        </div>
    </div>

<?=
Html::a(
        'Add Billing Profile',
        ['create'],
        [
            'class' =>
            'btn btn-primary',
        ]
)
?>

</div>

<div class="card shadow-sm">

    <div class="card-body p-0">

        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead class="table-light">
                    <tr>
                        <th>Client</th>
                        <th>Legal Name</th>
                        <th>State</th>
                        <th>GSTIN</th>
                        <th>PAN</th>
                        <th class="text-end">
                            Action
                        </th>
                    </tr>
                </thead>

                <tbody>

                    <?php
                    foreach (
                            $profiles as $profile
                    ):
                        ?>

                        <tr>

                            <td>
                                <?=
                                Html::encode(
                                        $profile->user
                                        ?->username ?? '-'
                                )
                                ?>
                            </td>

                            <td>
                                <?=
                                Html::encode(
                                        $profile->legal_name
                                )
                                ?>
                            </td>

                            <td>
    <?=
    Html::encode(
            $profile->state
    )
    ?>
                            </td>

                            <td>
                                <?=
                                Html::encode(
                                        $profile->gstin ?: '-'
                                )
                                ?>
                            </td>

                            <td>
                                <?=
                                Html::encode(
                                        $profile->pan ?: '-'
                                )
                                ?>
                            </td>

                            <td class="text-end">

                                <?=
                                Html::a(
                                        'Edit',
                                        [
                                            'update',
                                            'id' =>
                                            $profile->id,
                                        ],
                                        [
                                            'class' =>
                                            'btn btn-sm btn-outline-primary',
                                        ]
                                )
                                ?>

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
