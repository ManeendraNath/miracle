<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * The template's default status 10 is outside BaseIdentityTrait's 0-3 range,
 * so rows inserted without a status could never log in. 0 = Inactive.
 */
class m261030_100100_fix_user_status_default extends Migration
{

    public function safeUp(): void
    {
        $this->alterColumn('{{%user}}', 'status', $this->smallInteger()->notNull()->defaultValue(0));
    }

    public function safeDown(): void
    {
        $this->alterColumn('{{%user}}', 'status', $this->smallInteger()->notNull()->defaultValue(10));
    }
}
