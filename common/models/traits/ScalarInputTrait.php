<?php

declare(strict_types=1);

namespace common\models\traits;

/**
 * For forms with typed scalar properties: a posted array (e.g. LoginForm[username][]=x) becomes ''
 * instead of throwing a TypeError on assignment (or "Array to string" when the form is shown again),
 * so it ends as an ordinary validation error.
 */
trait ScalarInputTrait
{
    /** @param mixed $values whatever Model::load() found under the form name */
    public function setAttributes($values, $safeOnly = true): void
    {
        // A scalar where the form expects its fields (e.g. LoginForm=x) is ignored, as Yii itself does.
        parent::setAttributes(is_array($values) ? array_map(static fn($value) => is_array($value) ? '' : $value, $values) : [], $safeOnly);
    }
}
