<?php

declare(strict_types=1);

namespace common\models;

use common\models\traits\AutoTimestampTrait;
use common\helpers\SecretBox;
use yii\helpers\ArrayHelper;

class Hosting extends base\Hosting
{
    use AutoTimestampTrait;

    public const STATUS_INACTIVE = 0;
    public const STATUS_ACTIVE = 1;
    public const STATUS_SUSPENDED = 2;

    /** Form field: a new cPanel password to store; blank keeps the saved one. */
    public ?string $new_cpanel_password = null;

    /** cpanel_password itself is never mass-assigned: it only holds the encrypted value. */
    public function scenarios(): array
    {
        $scenarios = parent::scenarios();
        foreach ($scenarios as $name => $attributes) {
            $scenarios[$name] = array_map(static fn (string $a): string => $a === 'cpanel_password' ? '!cpanel_password' : $a, $attributes);
        }

        return $scenarios;
    }

    public function beforeSave($insert): bool
    {
        if (trim((string) $this->new_cpanel_password) !== '') {
            $this->cpanel_password = SecretBox::encrypt((string) $this->new_cpanel_password, 'cpanel-password');
            $this->new_cpanel_password = null;
        }

        return parent::beforeSave($insert);
    }

    /** Without TWO_FACTOR_KEY a password can't be stored encrypted: say so on the field, not as a 500. */
    public function validateCanEncrypt(string $attribute): void
    {
        if (!SecretBox::hasKey()) {
            $this->addError($attribute, 'Set TWO_FACTOR_KEY in .env before saving cPanel passwords.');
        }
    }

    /** The saved cPanel password in plain text; null when none is saved or it can't be decrypted. */
    public function getCpanelPasswordPlain(): ?string
    {
        $stored = (string) $this->cpanel_password;

        return $stored === '' ? null : SecretBox::decrypt($stored, 'cpanel-password');
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_SUSPENDED => 'Suspended / Expired',
        ];
    }

    public function rules(): array
    {
        return ArrayHelper::merge(
            parent::rules(),
            [
                [
                    ['renewal_cycle_months'],
                    'integer',
                    'min' => 1,
                ],

                [
                    ['renewal_increase_percent'],
                    'number',
                    'min' => 0,
                    'max' => 100,
                ],

                [
                    ['renewal_base_amount'],
                    'number',
                    'min' => 0,
                ],
                ['next_renewal_amount', 'number', 'min' => 0.01],
                ['next_renewal_amount', 'default', 'value' => null],

                [['primary_domain'], 'unique'],

                ['new_cpanel_password', 'string', 'max' => 100],
                ['new_cpanel_password', 'validateCanEncrypt'],

                [
                    ['status'],
                    'in',
                    'range' => array_keys(
                        self::getStatuses()
                    ),
                ],
            ]
        );
    }

    public function attributeLabels(): array
    {
        return ArrayHelper::merge(
            parent::attributeLabels(),
            [
                'user_id' => 'Client',
                'renewal_cycle_months' => 'Renewal Cycle (Months)',
                'renewal_increase_percent' => 'Renewal Increase (%)',
                'renewal_base_amount' => 'Base Renewal Amount',
                'new_cpanel_password' => 'cPanel Password',
            ]
        );
    }
}
