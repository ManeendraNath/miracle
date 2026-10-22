<?php

declare(strict_types=1);

use yii\db\Migration;

final class m261021_172000_create_user_login_history_table extends Migration
{

    public function safeUp(): void
    {
        $this->createTable(
                '{{%user_login_history}}',
                [
                    'id' =>
                    $this->bigPrimaryKey(),
                    'user_id' =>
                            $this->integer()
                            ->notNull(),
                    'ip_address' =>
                            $this->string(45)
                            ->null(),
                    'user_agent' =>
                            $this->string(1000)
                            ->null(),
                    'logged_in_at' =>
                            $this->integer()
                            ->notNull(),
                ]
        );

        $this->createIndex(
                'idx-user_login_history-user-date',
                '{{%user_login_history}}',
                [
                    'user_id',
                    'logged_in_at',
                ]
        );

        $this->addForeignKey(
                'fk-user_login_history-user',
                '{{%user_login_history}}',
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
                'fk-user_login_history-user',
                '{{%user_login_history}}'
        );

        $this->dropTable(
                '{{%user_login_history}}'
        );
    }
}
