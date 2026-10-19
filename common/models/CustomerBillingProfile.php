<?php

namespace common\models;


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
class CustomerBillingProfile extends CustomerBillingProfile
{

}
