<?php

declare(strict_types=1);

use yii\db\Migration;

final class m261021_171000_create_customer_preference_table extends Migration
{

    public function safeUp(): void
    {
        $this->createTable(
                '{{%customer_preference}}',
                [
                    'id' =>
                    $this->primaryKey(),
                    'user_id' =>
                            $this->integer()
                            ->notNull(),
                    'renewal_reminders' =>
                            $this->boolean()
                            ->notNull()
                            ->defaultValue(true),
                    'payment_reminders' =>
                            $this->boolean()
                            ->notNull()
                            ->defaultValue(true),
                    'service_expiry_notifications' =>
                            $this->boolean()
                            ->notNull()
                            ->defaultValue(true),
                    'support_notifications' =>
                            $this->boolean()
                            ->notNull()
                            ->defaultValue(true),
                    'created_at' =>
                            $this->integer()
                            ->notNull(),
                    'updated_at' =>
                            $this->integer()
                            ->notNull(),
                ]
        );

        $this->createIndex(
                'uq-customer_preference-user',
                '{{%customer_preference}}',
                'user_id',
                true
        );

        $this->addForeignKey(
                'fk-customer_preference-user',
                '{{%customer_preference}}',
                'user_id',
                '{{%user}}',
                'id',
                'CASCADE',
                'CASCADE'
        );
    }

    public function safeDown(): void
    {
        $this->dropForeignKey(
                'fk-customer_preference-user',
                '{{%customer_preference}}'
        );

        $this->dropTable(
                '{{%customer_preference}}'
        );
    }
}
