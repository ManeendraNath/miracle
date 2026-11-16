<?php

declare(strict_types=1);

namespace common\models\traits;

use common\models\User;

/**
 * For Domains, Hosting and Maintenance: a deleted customer is never billed, so none of their services may run
 * (be in RENEWABLE_STATUSES). The converse of User::validateNoActiveServices().
 */
trait CustomerNotDeletedTrait
{
    /** True when this service would run for a deleted customer. */
    public function runsForDeletedCustomer(): bool
    {
        return in_array((int) $this->status, static::RENEWABLE_STATUSES, true)
            && User::find()->where(['id' => (int) $this->user_id, 'status' => User::getStatusDeleted()])->exists();
    }

    public function validateCustomerNotDeleted(string $attribute): void
    {
        if ($this->runsForDeletedCustomer()) {
            $this->addError($attribute, 'This customer is deleted, so their services can only be inactive.');
        }
    }
}
