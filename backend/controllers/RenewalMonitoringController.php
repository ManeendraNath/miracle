<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\User;
use common\services\UpcomingExpiryService;
use Yii;
use yii\data\ArrayDataProvider;
use yii\helpers\ArrayHelper;

final class RenewalMonitoringController extends BaseController
{

    public function actionIndex()
    {
        $request = Yii::$app->request;

        $type = trim(
                (string) $request->get(
                        'type',
                        ''
                )
        );

        $userId = (int) $request->get(
                        'user_id',
                        0
                );

        $window = trim(
                (string) $request->get(
                        'window',
                        UpcomingExpiryService::WINDOW_60
                )
        );

        if (
                $type !== '' && !array_key_exists(
                        $type,
                        UpcomingExpiryService::getTypes()
                )
        ) {
            $type = '';
        }

        if (
                !array_key_exists(
                        $window,
                        UpcomingExpiryService::getWindows()
                )
        ) {
            $window = UpcomingExpiryService::WINDOW_60;
        }

        $userIdFilter = $userId > 0 ? $userId : null;

        $typeFilter = $type !== '' ? $type : null;

        $service = new UpcomingExpiryService();

        $items = $service->getItems(
                $typeFilter,
                $userIdFilter,
                $window
        );

        $summary = $service->getSummary(
                $typeFilter,
                $userIdFilter
        );

        $dataProvider = new ArrayDataProvider([
            'allModels' => $items,
            'pagination' => [
                'pageSize' => 20,
                'pageSizeLimit' => [
                    10,
                    100,
                ],
            ],
            'sort' => [
                'attributes' => [
                    'username' => [
                        'asc' => [
                            'username' => SORT_ASC,
                        ],
                        'desc' => [
                            'username' => SORT_DESC,
                        ],
                        'label' => 'Customer',
                    ],
                    'name' => [
                        'asc' => [
                            'name' => SORT_ASC,
                        ],
                        'desc' => [
                            'name' => SORT_DESC,
                        ],
                        'label' => 'Service',
                    ],
                    'type' => [
                        'asc' => [
                            'type' => SORT_ASC,
                        ],
                        'desc' => [
                            'type' => SORT_DESC,
                        ],
                        'label' => 'Type',
                    ],
                    'expiry_date' => [
                        'asc' => [
                            'expiry_date' => SORT_ASC,
                        ],
                        'desc' => [
                            'expiry_date' => SORT_DESC,
                        ],
                        'label' => 'Expiry',
                    ],
                    'days_remaining' => [
                        'asc' => [
                            'days_remaining' => SORT_ASC,
                        ],
                        'desc' => [
                            'days_remaining' => SORT_DESC,
                        ],
                        'label' => 'Remaining',
                    ],
                ],
                'defaultOrder' => [
                    'expiry_date' => SORT_ASC,
                    'name' => SORT_ASC,
                ],
            ],
            'key' => static function (
                    array $item
            ): string {
                return $item['type']
                . '-'
                . $item['id'];
            },
        ]);

        $customers = ArrayHelper::map(
                User::find()
                        ->select([
                            'id',
                            'username',
                            'email',
                        ])
                        ->orderBy([
                            'username' => SORT_ASC,
                        ])
                        ->all(),
                'id',
                static function (
                        User $user
                ): string {
                    $label = (string) $user->username;

                    if (!empty($user->email)) {
                        $label .= ' (' . $user->email . ')';
                    }

                    return $label;
                }
        );

        return $this->render(
                        'index',
                        [
                            'dataProvider' => $dataProvider,
                            'filters' => [
                                'type' => $type,
                                'user_id' => $userIdFilter,
                                'window' => $window,
                            ],
                            'customers' => $customers,
                            'summary' => $summary,
                        ]
                );
    }
}
