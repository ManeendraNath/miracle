<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Business details that were written into the email layouts and the privacy policy, now in Site Settings so the
 * portal carries any business's name: the email tagline and services line, and the Grievance Officer (IT Rules
 * 2021). Filled with what the pages said, so nothing visible changes.
 */
final class m261217_100000_site_setting_branding extends Migration
{
    private const COLUMNS = [
        'tagline' => 150,
        'services_line' => 200,
        'grievance_name' => 100,
        'grievance_email' => 255,
        'founded_year' => null, // a year: the email footer's "© 2015-2026"
    ];

    public function safeUp(): void
    {
        $table = $this->db->getTableSchema('{{%site_setting}}', true);
        $after = 'site_url';
        foreach (self::COLUMNS as $column => $length) {
            if ($table->getColumn($column) === null) {
                $this->addColumn('{{%site_setting}}', $column, ($length === null ? $this->smallInteger()->unsigned() : $this->string($length))->null()->after($after));
            }
            $after = $column;
        }
        $this->update('{{%site_setting}}', [
            'tagline' => 'Upgrade Your Business With Us',
            'services_line' => 'Website Development • Hosting • Domains • Maintenance',
            'grievance_name' => 'Maneendra Nath',
            'grievance_email' => 'support@miraclewebtechnologies.com, antmnt.trivedi@gmail.com',
            'founded_year' => 2015,
        // Only this business's own site: another installation starts with these empty.
        ], ['and', ['id' => 1], ['tagline' => null], ['grievance_name' => null], ['like', 'site_url', 'miraclewebtechnologies.com']]);
    }

    public function safeDown(): void
    {
        $table = $this->db->getTableSchema('{{%site_setting}}', true);
        foreach (array_reverse(array_keys(self::COLUMNS)) as $column) {
            if ($table->getColumn($column) !== null) {
                $this->dropColumn('{{%site_setting}}', $column);
            }
        }
    }
}
