<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Three monthly care plans for /care-plans, added as drafts: staff check the prices and what is included (and that
 * they can deliver it: backups and uptime checks need tools set up), then publish each one. Prices sit inside the
 * Nov 2026 market (India about ₹2,000-10,000 a month; abroad entry plans $49-99, mid $99-300). Skips a slug already
 * there; one staff deleted, or whose web address they changed, would come back on a re-run.
 */
final class m261216_110000_starter_care_plans extends Migration
{
    private const PLANS = [
        ['essential-care-plan', 'Essential Care', 1999, 59,
            'Monthly updates and backups for a small WordPress site, with 30 minutes of changes.',
            "We keep a small WordPress website up to date and backed up, check it is online, and make small changes when you need them.\n\nNot included: new pages or features beyond the time in the plan, paid plugin or theme licences, and hosting. Unused time does not carry over to the next month.",
            "WordPress, theme and plugin updates every month\nWeekly backups, the last 4 kept\nUptime check, with an email to you if it goes down\nSecurity scan every month\n30 minutes of small changes a month\nReplies within 2 working days"],
        ['business-care-plan', 'Business Care', 4999, 129,
            'Weekly updates, daily backups and 2 hours of changes a month for a business website.',
            "For a business website that needs to stay up and change often: updates every week, daily backups, and two hours a month of our time for changes and fixes.\n\nNot included: new features or redesigns beyond the time in the plan, paid plugin or theme licences, and hosting. Unused time does not carry over to the next month.",
            "WordPress, theme and plugin updates every week\nDaily backups, the last 30 kept\nUptime check, with an email to you if it goes down\nSecurity scan every month\n2 hours of changes and fixes a month\nA short monthly report\nReplies within 1 working day"],
        ['growth-care-plan', 'Growth Care', 9999, 249,
            'Everything in Business Care, with 5 hours of work a month and a monthly speed check.',
            "For a website that brings in business: everything in Business Care, five hours a month of our time for improvements, and a speed check every month with the fixes it finds.\n\nNot included: work beyond the time in the plan (quoted first), paid plugin or theme licences, and hosting. Unused time does not carry over to the next month.",
            "Everything in Business Care\n5 hours of changes and improvements a month\nSpeed check and fixes every month\nReplies the same working day"],
    ];

    public function safeUp(): void
    {
        $existing = (new \yii\db\Query())->select('slug')->from('{{%product}}')->column($this->db);
        $order = 50;
        foreach (self::PLANS as [$slug, $name, $inr, $usd, $summary, $description, $features]) {
            $order += 10;
            if (in_array($slug, $existing, true)) {
                continue;
            }
            $this->insert('{{%product}}', [
                'name' => $name,
                'slug' => $slug,
                'technology' => 'wordpress',
                'kind' => 'plan',
                'summary' => $summary,
                'description' => $description,
                'features' => $features,
                'price_single_inr' => $inr,
                'price_single_usd' => $usd,
                'status' => 0, // Draft: staff publish once happy with the price and what is included
                'sort_order' => $order,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }
    }

    public function safeDown(): void
    {
        // Only drafts never ordered: a published or sold plan is staff's now.
        $this->delete('{{%product}}', ['and', ['slug' => array_column(self::PLANS, 0), 'kind' => 'plan', 'status' => 0], ['not in', 'id', (new \yii\db\Query())->select('product_id')->from('{{%product_order}}')]]);
    }
}
