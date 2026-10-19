<?php

declare(strict_types=1);

use yii\db\Migration;

final class m261019_140000_add_client_billing_preferences extends Migration
{

    #[\Override]
    public function safeUp(): void
    {
        $this->addColumn(
                '{{%customer_billing_profile}}',
                'is_gst_applicable',
                $this->boolean()->notNull()->defaultValue(false)->after('pan')
        );

        $this->addColumn(
                '{{%customer_billing_profile}}',
                'default_discount_amount',
                $this->decimal(12, 2)->notNull()->defaultValue(0.00)->after('is_gst_applicable')
        );
    }

    #[\Override]
    public function safeDown(): void
    {
        $this->dropColumn('{{%customer_billing_profile}}', 'default_discount_amount');
        $this->dropColumn('{{%customer_billing_profile}}', 'is_gst_applicable');
    }
}
