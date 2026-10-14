<?php

declare(strict_types=1);

namespace common\models\traits;

use yii\behaviors\TimestampBehavior;

/**
 * Shared timestamp handling for application models whose generated
 * base models require created_at / updated_at during validation.
 *
 * Generated base models remain untouched.
 *
 * @author trivedi
 */
trait AutoTimestampTrait
{

    /**
     * Adds automatic timestamp handling on insert/update.
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        $behaviors['timestamp'] = [
            'class' => TimestampBehavior::class,
            'createdAtAttribute' => 'created_at',
            'updatedAtAttribute' => 'updated_at',
        ];

        return $behaviors;
    }

    /**
     * Generated base models may require these attributes
     * during validation, so initialise them beforehand.
     */
    protected function prepareTimestampAttributes(): void
    {
        $now = time();

        if ($this->isNewRecord && empty($this->created_at)) {
            $this->created_at = $now;
        }

        if (empty($this->updated_at)) {
            $this->updated_at = $now;
        }
    }
}
