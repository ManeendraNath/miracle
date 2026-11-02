<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * admin.status defaulted to 10 (the Yii template's "active"), but this app's active
 * status is 1 (Admin::getStatusActive()). A row inserted without a status got a value
 * no screen or login check recognises.
 */
class m261103_120000_fix_admin_status_default extends Migration
{
    public function safeUp()
    {
        $this->alterColumn('{{%admin}}', 'status', $this->smallInteger()->notNull()->defaultValue(1));
    }

    public function safeDown()
    {
        $this->alterColumn('{{%admin}}', 'status', $this->smallInteger()->notNull()->defaultValue(10));
    }
}
