<?php

declare(strict_types=1);

use yii\db\Migration;

/** Client quotes for the public home page, entered by staff or submitted by customers. Nothing shows until published. */
final class m261120_100000_create_testimonial_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%testimonial}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->null(),
            'client_name' => $this->string(255)->notNull(),
            'role' => $this->string(255)->null(),
            'company' => $this->string(255)->null(),
            'country' => $this->string(100)->null(),
            'quote' => $this->text()->notNull(),
            'is_published' => $this->boolean()->notNull()->defaultValue(false),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
            'consent_at' => $this->integer()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
        $this->createIndex('idx_testimonial_published', '{{%testimonial}}', ['is_published', 'sort_order']);
        $this->createIndex('ux_testimonial_user', '{{%testimonial}}', 'user_id', true);
        $this->addForeignKey('fk_testimonial_user', '{{%testimonial}}', 'user_id', '{{%user}}', 'id', 'SET NULL', 'CASCADE');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%testimonial}}');
    }
}
