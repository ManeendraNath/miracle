<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * The billing portal draft said PHP 8.2; its libraries need 8.4.1 (composer.json). Only the seeded wording changes,
 * so a description staff have edited is left alone.
 */
final class m261220_100000_billing_portal_php_version extends Migration
{
    public function safeUp(): void
    {
        $this->execute(
            "UPDATE {{%product}} SET description = REPLACE(description, 'PHP 8.2 or newer and MySQL 8', 'PHP 8.4.1 or newer and MySQL 8')"
            . " WHERE slug = 'billing-portal'"
        );
    }

    public function safeDown(): void
    {
        $this->execute(
            "UPDATE {{%product}} SET description = REPLACE(description, 'PHP 8.4.1 or newer and MySQL 8', 'PHP 8.2 or newer and MySQL 8')"
            . " WHERE slug = 'billing-portal'"
        );
    }
}
