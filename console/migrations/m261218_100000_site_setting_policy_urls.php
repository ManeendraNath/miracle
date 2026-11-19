<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Portal-only copies (PORTAL_ONLY) have no policy pages of their own: the business's terms, refund and privacy
 * pages on its own website, linked where a customer pays and from the portal's footer.
 */
final class m261218_100000_site_setting_policy_urls extends Migration
{
    private const COLUMNS = ['terms_url', 'refund_url', 'privacy_url'];

    public function safeUp(): void
    {
        $after = 'grievance_email';
        foreach (self::COLUMNS as $column) {
            if ($this->db->getTableSchema('{{%site_setting}}', true)->getColumn($column) === null) {
                $this->addColumn('{{%site_setting}}', $column, $this->string(255)->null()->after($after));
            }
            $after = $column;
        }
    }

    public function safeDown(): void
    {
        foreach (array_reverse(self::COLUMNS) as $column) {
            if ($this->db->getTableSchema('{{%site_setting}}', true)->getColumn($column) !== null) {
                $this->dropColumn('{{%site_setting}}', $column);
            }
        }
    }
}
