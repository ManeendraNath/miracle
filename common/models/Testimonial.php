<?php

declare(strict_types=1);

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * A client quote shown on the public home page once published.
 *
 * @property int $id
 * @property int|null $user_id Set when the customer submitted it themselves
 * @property string $client_name
 * @property string|null $role
 * @property string|null $company
 * @property string|null $country
 * @property string $quote
 * @property bool|int $is_published
 * @property int $sort_order
 * @property int|null $consent_at When the customer agreed to it being published
 * @property int $created_at
 * @property int $updated_at
 */
class Testimonial extends ActiveRecord
{
    public const MAX_ON_HOME = 6;
    /** The portal form: the customer can't publish or order their own quote. */
    public const SCENARIO_CUSTOMER = 'customer';

    /** The portal form's "you may publish this" checkbox. */
    public $consent;

    public static function tableName(): string
    {
        return '{{%testimonial}}';
    }

    public function behaviors(): array
    {
        return [TimestampBehavior::class];
    }

    public function rules(): array
    {
        return [
            [['client_name', 'quote'], 'required'],
            [['client_name', 'role', 'company', 'country', 'quote'], 'trim'],
            [['client_name', 'role', 'company'], 'string', 'max' => 255],
            ['country', 'string', 'max' => 100],
            ['quote', 'string', 'max' => 1000],
            [['role', 'company', 'country'], 'default', 'value' => null],
            ['is_published', 'boolean', 'except' => self::SCENARIO_CUSTOMER],
            ['sort_order', 'integer', 'except' => self::SCENARIO_CUSTOMER],
            ['sort_order', 'default', 'value' => 0, 'except' => self::SCENARIO_CUSTOMER],
            ['consent', 'required', 'requiredValue' => 1, 'on' => self::SCENARIO_CUSTOMER, 'message' => 'Please agree so we can publish your words.'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'client_name' => 'Client Name',
            'consent' => 'You may publish my name, details and words on your website',
            'is_published' => 'Show on the website',
            'sort_order' => 'Display Order',
        ];
    }

    /** @return self[] */
    public static function forHomePage(): array
    {
        return self::find()
            ->where(['is_published' => 1])
            ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_DESC])
            ->limit(self::MAX_ON_HOME)
            ->all();
    }

    /** "Role, Company, Country", leaving out whatever is empty. */
    public function byline(): string
    {
        return implode(', ', array_filter([$this->role, $this->company, $this->country]));
    }
}
