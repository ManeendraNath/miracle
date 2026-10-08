<?php

use yii\db\Migration;

class m261001_150007_create_auth_item_child_table extends Migration
{

    private $tableName = '{{%auth_item_child}}';

    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // High-performance collation optimization for enterprise setups
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }
        if ($this->db->getTableSchema($this->tableName, true) === null) {
            $this->createTable($this->tableName, [
                'parent' => $this->string(64)->notNull(),
                'child' => $this->string(64)->notNull(),
            ]);
            
            $this->addPrimaryKey('pk-auth_item_child', $this->tableName, ['parent', 'child']);

            // Seed Initial Auth Item Children Links
            $this->batchInsert($this->tableName, ['parent', 'child'], [
                ['Admin', 'assignRolesToUsers'],
                ['Admin', 'changeOwnPassword'],
                ['Admin', 'changeUserPassword'],
                ['Admin', 'createUsers'],
                ['Admin', 'deleteUsers'],
                ['Admin', 'editUsers'],
                ['Admin', 'viewUsers'],
                ['assignRolesToUsers', '/user-management/user-permission/set'],
                ['assignRolesToUsers', '/user-management/user-permission/set-roles'],
                ['assignRolesToUsers', 'viewUserRoles'],
                ['assignRolesToUsers', 'viewUsers'],
                ['changeOwnPassword', '/user-management/auth/change-own-password'],
                ['changeUserPassword', '/user-management/user/change-password'],
                ['changeUserPassword', 'viewUsers'],
                ['createUsers', '/user-management/user/create'],
                ['createUsers', 'viewUsers'],
                ['deleteUsers', '/user-management/user/bulk-delete'],
                ['deleteUsers', '/user-management/user/delete'],
                ['deleteUsers', 'viewUsers'],
                ['editUserEmail', 'viewUserEmail'],
                ['editUsers', '/user-management/user/bulk-activate'],
                ['editUsers', '/user-management/user/bulk-deactivate'],
                ['editUsers', '/user-management/user/update'],
                ['editUsers', 'viewUsers'],
                ['viewUsers', '/user-management/user/grid-page-size'],
                ['viewUsers', '/user-management/user/index'],
                ['viewUsers', '/user-management/user/view']
            ]);
        }
    }

    public function safeDown()
    {
        if ($this->db->getTableSchema($this->tableName, true) !== null) {
            $this->dropTable($this->tableName);
        }
    }
}
