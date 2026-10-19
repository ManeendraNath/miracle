<?php

declare(strict_types=1);

namespace common\models\traits;

/**
 * Automatically manages created_at and updated_at UNIX timestamps.
 *
 * This trait is intended for ActiveRecord models whose generated
 * base-model validation rules require created_at / updated_at.
 *
 * Important:
 * - Timestamps are populated when saving an ActiveRecord.
 * - Calling validate() on a SearchModel must not create timestamps.
 * - created_at is populated only for new records.
 * - updated_at is refreshed on every save.
 *
 * @author trivedi
 */
trait AutoTimestampTrait
{
    /**
     * Flag indicating that the model is currently being saved.
     */
    private bool $_autoTimestampSaving = false;

    /**
     * Marks the model as being saved before Yii starts validation.
     *
     * @param bool $runValidation
     * @param array|null $attributeNames
     *
     * @return bool
     */
    public function save($runValidation = true, $attributeNames = null)
    {
        $this->_autoTimestampSaving = true;
        try {
            return parent::save($runValidation, $attributeNames);
        } finally {
            $this->_autoTimestampSaving = false;
        }
    }

    /**
     * Populate timestamps before validation only during save().
     */
    public function beforeValidate(): bool
    {
        if ($this->_autoTimestampSaving) {
            $this->prepareTimestampAttributes();
        }
        return parent::beforeValidate();
    }

    /**
     * Populate timestamp attributes.
     */
    protected function prepareTimestampAttributes(): void
    {
        $now = time();
        if ($this->isNewRecord && empty($this->created_at)) {
            $this->created_at = $now;
        }
        $this->updated_at = $now;
    }
}