<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Foreign clients: the billing profile gets a country (existing profiles are Indian), and
 * invoices keep a copy of it like the rest of the client address. NULL on old invoices = India.
 */
final class m261106_100000_add_country_for_foreign_clients extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%customer_billing_profile}}', 'country', $this->string(100)->notNull()->defaultValue('India')->after('postal_code'));
        $this->addColumn('{{%invoice}}', 'client_country', $this->string(100)->null()->after('client_postal_code'));
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%invoice}}', 'client_country');
        $this->dropColumn('{{%customer_billing_profile}}', 'country');
    }
}
