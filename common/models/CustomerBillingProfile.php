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
 * @property string $country
 * @property string|null $gstin
 * @property string|null $pan
 * @property int $created_at
 * @property int $updated_at
 *
 * @property User $user
 */
class CustomerBillingProfile extends base\CustomerBillingProfile
{
    use traits\ScalarInputTrait;
    use AutoTimestampTrait;

    /** Customer portal: only address/identity fields are safe; GST applicability and discount stay admin-only. */
    public const SCENARIO_CUSTOMER = 'customer';

    /**
     * GST state / UT name => GST state code (CBIC list). state_code is always derived from state.
     * Legacy codes 25 (Daman and Diu, merged into 26) and 28 (Andhra Pradesh before 2014, now 37) are left out.
     */
    public const GST_STATES = [
        'Jammu and Kashmir' => '01',
        'Himachal Pradesh' => '02',
        'Punjab' => '03',
        'Chandigarh' => '04',
        'Uttarakhand' => '05',
        'Haryana' => '06',
        'Delhi' => '07',
        'Rajasthan' => '08',
        'Uttar Pradesh' => '09',
        'Bihar' => '10',
        'Sikkim' => '11',
        'Arunachal Pradesh' => '12',
        'Nagaland' => '13',
        'Manipur' => '14',
        'Mizoram' => '15',
        'Tripura' => '16',
        'Meghalaya' => '17',
        'Assam' => '18',
        'West Bengal' => '19',
        'Jharkhand' => '20',
        'Odisha' => '21',
        'Chhattisgarh' => '22',
        'Madhya Pradesh' => '23',
        'Gujarat' => '24',
        'Dadra and Nagar Haveli and Daman and Diu' => '26',
        'Maharashtra' => '27',
        'Karnataka' => '29',
        'Goa' => '30',
        'Lakshadweep' => '31',
        'Kerala' => '32',
        'Tamil Nadu' => '33',
        'Puducherry' => '34',
        'Andaman and Nicobar Islands' => '35',
        'Telangana' => '36',
        'Andhra Pradesh' => '37',
        'Ladakh' => '38',
        'Other Territory' => '97',
    ];

    public const COUNTRY_INDIA = 'India';

    /** GST place-of-supply for clients outside India (state code 96 on the GST portal). */
    public const FOREIGN_STATE = 'Foreign Country';
    public const FOREIGN_STATE_CODE = '96';

    /** Outside India: no GST, no Indian state / GSTIN / PAN. An empty country is the India default. */
    public function isForeign(): bool
    {
        $country = trim((string) $this->country);

        return $country !== '' && $country !== self::COUNTRY_INDIA;
    }

    /** @return string[] country names */
    public static function countries(): array
    {
        return require __DIR__ . '/data/countries.php';
    }

    /** @return array<string, string> India first, then the rest A-Z, for dropdowns */
    public static function countryOptions(): array
    {
        $names = self::countries();

        return [self::COUNTRY_INDIA => self::COUNTRY_INDIA] + array_combine($names, $names);
    }

    #[\Override]
    public function afterSave($insert, $changedAttributes): void
    {
        parent::afterSave($insert, $changedAttributes);
        if (\common\services\customer\IncompleteProfileReminderService::missingFields($this) === []) {
            \common\services\customer\IncompleteProfileReminderService::clearReminder((int) $this->user_id);
        }
    }

    public function scenarios(): array
    {
        $scenarios = parent::scenarios();
        // "!" = validated but never mass-assigned.
        $scenarios[self::SCENARIO_CUSTOMER] = [
            // Not country: outside India means no GST, so only an admin may set it.
            'legal_name', 'address_line_1', 'address_line_2', 'city', 'state', 'postal_code', 'gstin', 'pan',
            '!state_code', '!user_id',
        ];

        return $scenarios;
    }

    public function rules(): array
    {
        return ArrayHelper::merge(
            // Normalise first so the base length checks see the trimmed value ('' becomes null there).
            [
                [['legal_name', 'address_line_1', 'address_line_2', 'city', 'state', 'postal_code', 'country', 'gstin', 'pan'], 'trim'],
                // Outside India the GST fields don't apply: fixed state, no GSTIN/PAN.
                ['state', 'filter', 'filter' => fn ($state) => $this->isForeign() ? self::FOREIGN_STATE : $state],
                [['gstin', 'pan'], 'filter', 'filter' => fn ($value) => $this->isForeign() ? null : $value],
                [['gstin', 'pan'], 'filter', 'filter' => 'strtoupper', 'skipOnEmpty' => true],
            ],
            parent::rules(),
            [
                ['country', 'required'],
                ['country', 'in', 'range' => self::countries(), 'strict' => true, 'message' => 'Select a country from the list.'],
                [
                    'state', 'in', 'range' => array_keys(self::GST_STATES), 'strict' => true, 'message' => 'Select a state from the list.',
                    'when' => fn (): bool => !$this->isForeign(), 'whenClient' => self::WHEN_INDIA_JS,
                ],
                ['state_code', 'filter', 'filter' => fn ($code) => $this->isForeign() ? self::FOREIGN_STATE_CODE : (self::GST_STATES[$this->state] ?? $code)],
                [['legal_name', 'state', 'state_code'], 'required'],
                [['address_line_1', 'city', 'postal_code'], 'required', 'on' => self::SCENARIO_CUSTOMER],
                [
                    'postal_code', 'match', 'pattern' => '/^\d{6}$/', 'message' => 'Enter a valid 6-digit PIN code.',
                    'when' => fn (): bool => !$this->isForeign(), 'whenClient' => self::WHEN_INDIA_JS,
                ],
                [
                    'gstin', 'match', 'pattern' => '/^\d{2}[A-Z]{5}\d{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/',
                    'message' => 'Enter a valid 15-character GSTIN (e.g. 07ABCDE1234F1Z2).',
                ],
                ['pan', 'match', 'pattern' => '/^[A-Z]{5}\d{4}[A-Z]$/', 'message' => 'Enter a valid PAN (e.g. ABCDE1234F).'],
                ['gstin', 'validateGstinMatches'],
            ]
        );
    }

    /** Browser-side twin of !isForeign() for the forms' client validation. */
    private const WHEN_INDIA_JS = "function () { var c = $('#customerbillingprofile-country').val(); return !c || c === 'India'; }";

    /** No GST on supplies to clients outside India, whatever was set before the country changed. */
    public function beforeSave($insert): bool
    {
        if ($this->isForeign()) {
            $this->is_gst_applicable = 0;
        }

        return parent::beforeSave($insert);
    }

    /** GSTIN = state code (2) + PAN (10) + 3 chars, so it must agree with the chosen state and the PAN. */
    public function validateGstinMatches(string $attribute): void
    {
        if ($this->hasErrors('gstin')) {
            return;
        }
        if ($this->gstin[14] !== self::gstinCheckChar($this->gstin)) {
            $this->addError('gstin', 'This GSTIN is not valid (check digit mismatch). Please check it for typing mistakes.');
            return;
        }
        if ($this->hasErrors('state')) {
            return;
        }
        if (strncmp($this->gstin, (string) $this->state_code, 2) !== 0) {
            $this->addError('gstin', "GSTIN must start with {$this->state_code}, the GST code of {$this->state}.");
        }
        if ($this->pan !== null && !$this->hasErrors('pan') && substr($this->gstin, 2, 10) !== $this->pan) {
            $this->addError('pan', 'PAN must match characters 3-12 of the GSTIN.');
        }
    }

    /** GSTN check character: weighted base-36 sum of the first 14 characters (weights 1, 2, 1, 2...). */
    public static function gstinCheckChar(string $gstin): string
    {
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $sum = 0;
        for ($i = 0; $i < 14; $i++) {
            $product = strpos($chars, $gstin[$i]) * ($i % 2 + 1);
            $sum += intdiv($product, 36) + $product % 36;
        }
        return $chars[(36 - $sum % 36) % 36];
    }

    /** @return array<string, string> state name => label, for dropdowns */
    public static function stateOptions(): array
    {
        $options = [];
        foreach (self::GST_STATES as $name => $code) {
            $options[$name] = "{$name} ({$code})";
        }

        return $options;
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
                            'postal_code' => 'PIN / Postal Code',
                            'country' => 'Country',
                            'gstin' => 'GSTIN',
                            'pan' => 'PAN',
                        ]
        );
    }
}
