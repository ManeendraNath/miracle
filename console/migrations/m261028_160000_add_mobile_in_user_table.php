<?php

use yii\db\Migration;

final class m261028_160000_add_mobile_in_user_table extends Migration
{

    public function safeUp(): void
    {
        $this->addColumn('user', 'mobile', $this->string(20)->unique()->after('email'));
        $this->addColumn('user', 'whatsapp', $this->string(20)->unique()->after('mobile'));
        $this->createIndex('idx_user_mobile', 'user', 'mobile', true);
    }

    public function safeDown(): void
    {
        $this->dropIndex('idx_user_mobile', 'user');
        $this->dropColumn('user', 'mobile');
        $this->dropColumn('user', 'whatsapp');
    }
}
