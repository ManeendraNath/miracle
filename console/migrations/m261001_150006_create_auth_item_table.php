<?php

use yii\db\Migration;

class m261001_150006_create_auth_item_table extends Migration
{

    private $tableName = '{{%auth_item}}';

    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // High-performance collation optimization for enterprise setups
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }
        if ($this->db->getTableSchema($this->tableName, true) === null) {
            $this->createTable($this->tableName, [
                'name' => $this->string(64)->notNull(),
                'type' => $this->integer(11)->notNull(),
                'description' => $this->text()->defaultValue(null),
                'rule_name' => $this->string(64)->defaultValue(null),
                'data' => $this->text()->defaultValue(null),
                'group_code' => $this->string(64)->defaultValue(null),
                'created_at' => $this->integer(11)->defaultValue(null),
                'updated_at' => $this->integer(11)->defaultValue(null),
            ]);

            $this->addPrimaryKey('pk-auth_item-name', $this->tableName, 'name');
            $this->createIndex('idx-auth_item-type', $this->tableName, 'type');

            // Seed Initial Auth Items
            $this->batchInsert($this->tableName, ['name', 'type', 'description', 'rule_name', 'data', 'created_at', 'updated_at', 'group_code'], [
                ['/*', 3, null, null, null, 1652196352, 1652196352, null],
                ['//*', 3, null, null, null, 1652196352, 1652196352, null],
                ['//controller', 3, null, null, null, 1652196352, 1652196352, null],
                ['//crud', 3, null, null, null, 1652196352, 1652196352, null],
                ['//extension', 3, null, null, null, 1652196352, 1652196352, null],
                ['//form', 3, null, null, null, 1652196352, 1652196352, null],
                ['//index', 3, null, null, null, 1652196352, 1652196352, null],
                ['//model', 3, null, null, null, 1652196352, 1652196352, null],
                ['//module', 3, null, null, null, 1652196352, 1652196352, null],
                ['/asset/*', 3, null, null, null, 1652196352, 1652196352, null],
                ['/asset/compress', 3, null, null, null, 1652196352, 1652196352, null],
                ['/asset/template', 3, null, null, null, 1652196352, 1652196352, null],
                ['/cache/*', 3, null, null, null, 1652196352, 1652196352, null],
                ['/cache/flush', 3, null, null, null, 1652196352, 1652196352, null],
                ['/cache/flush-all', 3, null, null, null, 1652196352, 1652196352, null],
                ['/cache/flush-schema', 3, null, null, null, 1652196352, 1652196352, null],
                ['/cache/index', 3, null, null, null, 1652196352, 1652196352, null],
                ['/fixture/*', 3, null, null, null, 1652196352, 1652196352, null],
                ['/fixture/load', 3, null, null, null, 1652196352, 1652196352, null],
                ['/fixture/unload', 3, null, null, null, 1652196352, 1652196352, null],
                ['/gii/*', 3, null, null, null, 1652196352, 1652196352, null],
                ['/gii/default/*', 3, null, null, null, 1652196352, 1652196352, null],
                ['/gii/default/action', 3, null, null, null, 1652196352, 1652196352, null],
                ['/gii/default/diff', 3, null, null, null, 1652196352, 1652196352, null],
                ['/gii/default/index', 3, null, null, null, 1652196352, 1652196352, null],
                ['/gii/default/preview', 3, null, null, null, 1652196352, 1652196352, null],
                ['/gii/default/view', 3, null, null, null, 1652196352, 1652196352, null],
                ['/help/*', 3, null, null, null, 1652196352, 1652196352, null],
                ['/help/index', 3, null, null, null, 1652196352, 1652196352, null],
                ['/help/list', 3, null, null, null, 1652196352, 1652196352, null],
                ['/help/list-action-options', 3, null, null, null, 1652196352, 1652196352, null],
                ['/help/usage', 3, null, null, null, 1652196352, 1652196352, null],
                ['/message/*', 3, null, null, null, 1652196352, 1652196352, null],
                ['/message/config', 3, null, null, null, 1652196352, 1652196352, null],
                ['/message/config-template', 3, null, null, null, 1652196352, 1652196352, null],
                ['/message/extract', 3, null, null, null, 1652196352, 1652196352, null],
                ['/migrate/*', 3, null, null, null, 1652196352, 1652196352, null],
                ['/migrate/create', 3, null, null, null, 1652196352, 1652196352, null],
                ['/migrate/down', 3, null, null, null, 1652196352, 1652196352, null],
                ['/migrate/fresh', 3, null, null, null, 1652196352, 1652196352, null],
                ['/migrate/history', 3, null, null, null, 1652196352, 1652196352, null],
                ['/migrate/mark', 3, null, null, null, 1652196352, 1652196352, null],
                ['/migrate/new', 3, null, null, null, 1652196352, 1652196352, null],
                ['/migrate/redo', 3, null, null, null, 1652196352, 1652196352, null],
                ['/migrate/to', 3, null, null, null, 1652196352, 1652196352, null],
                ['/migrate/up', 3, null, null, null, 1652196352, 1652196352, null],
                ['/serve/*', 3, null, null, null, 1652196352, 1652196352, null],
                ['/serve/index', 3, null, null, null, 1652196352, 1652196352, null],
                ['/user-management/*', 3, null, null, null, 1652196352, 1652196352, null],
                ['/user-management/auth/change-own-password', 3, null, null, null, 1652196353, 1652196353, null],
                ['/user-management/user-permission/set', 3, null, null, null, 1652196353, 1652196353, null],
                ['/user-management/user-permission/set-roles', 3, null, null, null, 1652196353, 1652196353, null],
                ['/user-management/user/bulk-activate', 3, null, null, null, 1652196352, 1652196352, null],
                ['/user-management/user/bulk-deactivate', 3, null, null, null, 1652196352, 1652196352, null],
                ['/user-management/user/bulk-delete', 3, null, null, null, 1652196352, 1652196352, null],
                ['/user-management/user/change-password', 3, null, null, null, 1652196352, 1652196352, null],
                ['/user-management/user/create', 3, null, null, null, 1652196352, 1652196352, null],
                ['/user-management/user/delete', 3, null, null, null, 1652196352, 1652196352, null],
                ['/user-management/user/grid-page-size', 3, null, null, null, 1652196352, 1652196352, null],
                ['/user-management/user/index', 3, null, null, null, 1652196352, 1652196352, null],
                ['/user-management/user/update', 3, null, null, null, 1652196352, 1652196352, null],
                ['/user-management/user/view', 3, null, null, null, 1652196352, 1652196352, null],
                ['Admin', 1, 'Admin', null, null, 1652196352, 1652196352, null],
                ['assignRolesToUsers', 2, 'Assign roles to users', null, null, 1652196353, 1652196353, 'userManagement'],
                ['bindUserToIp', 2, 'Bind user to IP', null, null, 1652196353, 1652196353, 'userManagement'],
                ['changeOwnPassword', 2, 'Change own password', null, null, 1652196353, 1652196353, 'userCommonPermissions'],
                ['changeUserPassword', 2, 'Change user password', null, null, 1652196352, 1652196352, 'userManagement'],
                ['commonPermission', 2, 'Common permission', null, null, 1652196351, 1652196351, null],
                ['createUsers', 2, 'Create users', null, null, 1652196352, 1652196352, 'userManagement'],
                ['deleteUsers', 2, 'Delete users', null, null, 1652196352, 1652196352, 'userManagement'],
                ['editUserEmail', 2, 'Edit user email', null, null, 1652196353, 1652196353, 'userManagement'],
                ['editUsers', 2, 'Edit users', null, null, 1652196352, 1652196352, 'userManagement'],
                ['viewRegistrationIp', 2, 'View registration IP', null, null, 1652196353, 1652196353, 'userManagement'],
                ['viewUserEmail', 2, 'View user email', null, null, 1652196353, 1652196353, 'userManagement'],
                ['viewUserRoles', 2, 'View user roles', null, null, 1652196353, 1652196353, 'userManagement'],
                ['viewUsers', 2, 'View users', null, null, 1652196352, 1652196352, 'userManagement'],
                ['viewVisitLog', 2, 'View visit log', null, null, 1652196353, 1652196353, 'userManagement']
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
