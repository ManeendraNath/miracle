<?php

declare(strict_types=1);

namespace common\models;

use common\models\traits\AutoTimestampTrait;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "customer_billing_profile".
 *
 * @property int $id
 * @property int $user_id
 * @property string $legal_name
 * @property string|null $address_line_1
 * @property string|null $address_line_2
 * @property string|null $city
 * @property string $state
 * @property string|null $state_code
 * @property string|null $postal_code
 * @property string|null $gstin
 * @property string|null $pan
 * @property int $created_at
 * @property int $updated_at
 *
 * @property User $user
 */
class CustomerBillingProfile extends base\CustomerBillingProfile
{

    use AutoTimestampTrait;

    public function rules(): array
    {
        return ArrayHelper::merge(
                        parent::rules(),
                        [
                            [['legal_name', 'state', 'state_code'], 'trim'],
                            [['legal_name', 'state', 'state_code'], 'required'],
                            ['state_code', 'string', 'max' => 10],
                            ['gstin', 'string', 'max' => 15],
                            ['pan', 'string', 'max' => 10],
                        ]
                );
    }

    public function attributeLabels(): array
    {
        return ArrayHelper::merge(
                        parent::attributeLabels(),
                        [
                            'user_id' => 'Client',
                            'legal_name' => 'Legal Name',
                            'address_line_1' => 'Address Line 1',
                            'address_line_2' => 'Address Line 2',
                            'state_code' => 'State Code',
                            'postal_code' => 'Postal Code',
                            'gstin' => 'GSTIN',
                            'pan' => 'PAN',
                        ]
                );
    }
}
