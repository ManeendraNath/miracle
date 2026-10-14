<?php

declare(strict_types=1);

namespace common\models\traits;

/**
 * Shared password management for identity models.
 *
 * Expected model methods:
 * - setPassword(string $password): void
 * - generateAuthKey(): void
 *
 * Expected model attributes:
 * - password_hash
 * - auth_key
 *
 * @author trivedi
 */
trait PasswordManagementTrait
{

    /**
     * Plain-text password from create/update forms.
     *
     * This value is never stored directly in the database.
     */
    public ?string $password = null;

    /**
     * Shared password validation rules.
     */
    public function getPasswordRules(): array
    {
        return [
            ['password', 'required', 'when' => static function ($model): bool {
                    return $model->isNewRecord;
                },
            ],
            ['password', 'string', 'min' => 8, 'max' => 255, 'skipOnEmpty' => true],
        ];
    }

    /**
     * Prepares password/authentication fields before validation.
     *
     * Create:
     * - hashes the supplied password
     * - generates auth_key when missing
     *
     * Update:
     * - hashes password only when a new password is supplied
     * - blank password preserves existing password_hash
     */
    protected function preparePasswordAttributes(): void
    {
        if ($this->password !== null && $this->password !== '') {
            $this->setPassword($this->password);
        }

        if ($this->isNewRecord && empty($this->auth_key)) {
            $this->generateAuthKey();
        }
    }

    /**
     * Clear the plain-text password.
     *
     * Useful before rendering a form after failed validation.
     */
    public function clearPlainPassword(): void
    {
        $this->password = null;
    }
}
