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
    public function setAttributes($values, $safeOnly = true): void
    {
        parent::setAttributes(array_map(static fn($value) => is_array($value) ? '' : $value, $values), $safeOnly);
    }
}
