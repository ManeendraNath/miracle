<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Drops tables nothing in the app uses: Yii's RBAC tables (authManager is not configured; staff access is the app's own
 * code, and auth_assignment is empty: their rows are route permissions seeded by an old user-management add-on) and two
 * coupon tracking tables (coupon use is recorded in coupon_redemption). Can be run again: a missing table is skipped.
 * Down recreates them empty, with the seeds, by running their original create migrations again.
 */
final class m261130_100000_drop_unused_tables extends Migration
{
    /** Children before the tables they could point to. */
    private const TABLES = ['auth_assignment', 'auth_item_child', 'auth_item', 'auth_rule', 'auth_item_group', 'coupon_redeem_track', 'coupon_email_track'];

    /** In creation order. */
    private const CREATED_BY = [
        'm261001_150004_create_auth_rule_table',
        'm261001_150005_create_auth_item_group_table',
        'm261001_150006_create_auth_item_table',
        'm261001_150007_create_auth_item_child_table',
        'm261001_150009_create_coupon_email_track_table',
        'm261001_170504_create_auth_assignment_table',
        'm261001_170511_create_coupon_redeem_track_table',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if ($this->db->getTableSchema('{{%' . $table . '}}', true) !== null) {
                $this->dropTable('{{%' . $table . '}}');
            }
        }
    }

    public function down(): void
    {
        foreach (self::CREATED_BY as $class) {
            require_once __DIR__ . '/' . $class . '.php';
            // Each one skips a table that already exists.
            (new $class(['db' => $this->db, 'compact' => $this->compact]))->safeUp();
        }
    }
}
