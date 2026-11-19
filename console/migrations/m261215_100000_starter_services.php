<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Ten fixed-price services for /services, added as drafts: staff check the prices and wording, then publish each one
 * (Themes & Plugins in admin). Prices sit between marketplace gigs and agency rates (Nov 2026 research). Skips a
 * slug already there; a service staff deleted, or whose web address they changed, would come back on a re-run.
 */
final class m261215_100000_starter_services extends Migration
{
    private const SERVICES = [
        ['wordpress-speed-up', 'WordPress Speed-Up', 'wordpress', 3, 6999, 149,
            'Make a slow WordPress site load faster, with a before-and-after speed report.',
            "We measure your site with Google PageSpeed Insights, find what slows it down, and fix it: caching, image compression, unused scripts and styles, heavy plugins and database clean-up.\n\nYou get a before-and-after report. Lab scores usually improve at once; Google's real-visitor data (Core Web Vitals in Search Console) takes about four weeks to catch up, for sites with enough visitors to have it. We do not promise a particular score: how fast a site can get depends on its hosting, theme and plugins.",
            "Speed test before and after\nPage caching and browser caching\nImages compressed and served in WebP\nUnused scripts and styles removed or delayed\nDatabase clean-up\nA full backup before we start"],
        ['hacked-site-clean-up', 'Hacked Website Clean-Up', 'wordpress', 2, 7499, 179,
            'Remove malware from a hacked WordPress site, close the likely ways in, and ask Google to lift any warning.',
            "We take a backup, scan the files and database, remove the malware, backdoors and unknown admin accounts, and replace WordPress core files with clean copies.\n\nThen we update everything, change the passwords, add basic hardening, and, if Google shows a warning on your site, request a review. If the same infection comes back within 30 days, we clean it again at no cost, as long as no new plugins or themes were added and the new passwords were kept.",
            "Malware and backdoors removed from files and database\nUnknown admin users removed\nWordPress, theme and plugins updated\nPasswords and security keys changed\nBasic hardening\nGoogle warning review request, where needed\nFree re-clean if the same infection comes back within 30 days"],
        ['website-move-to-new-hosting', 'Website Move to New Hosting', 'any', 3, 3999, 99,
            'Move your website, files, database and email settings to a new host.',
            "We copy your website and database to the new hosting, check that every page works there, and then point your domain to it, so visitors move over, usually with little downtime. Nothing is deleted from the old host until you confirm all is well.\n\nWorks for WordPress, plain HTML and PHP sites. Up to 3 mailboxes can be moved too: say so in your brief. For a site over 10 GB or more mailboxes, we quote the difference first, or give a full refund if you would rather not.",
            "Files and database copied and checked\nSite tested on the new host before switching\nDomain and DNS pointed to the new host\nSSL set up on the new host\nNothing deleted from the old host until you confirm all is well"],
        ['ssl-and-https-fix', 'SSL and HTTPS Fix', 'any', 1, 1999, 49,
            'Install SSL, send every visitor to https, and fix "not secure" warnings and mixed content.',
            "We install a free SSL certificate (or the one you bought), redirect all http addresses to https, and fix the mixed-content warnings that keep the padlock from showing.\n\nIf the certificate is paid, its cost is not included.",
            "SSL certificate installed\nAll pages redirected to https\nMixed-content warnings fixed\nAuto-renewal checked"],
        ['email-authentication-setup', 'Email Authentication (SPF, DKIM, DMARC)', 'any', 2, 2999, 79,
            'Set up the records that prove your email is really from your domain, so it is less likely to land in spam.',
            "We set up the DNS records that prove your email is really from you: SPF, DKIM and DMARC. That covers your mailboxes and up to two other services that send mail as you, such as your website's contact form or a newsletter tool.\n\nWe also check your website's contact form reaches you, and send you test results.",
            "SPF, DKIM and DMARC records set up\nUp to 2 other sending services covered\nWebsite contact form email checked\nDeliverability test results"],
        ['wordpress-error-fix', 'WordPress Error Fix', 'wordpress', 1, 3499, 99,
            'Fix a "critical error", white screen, database connection error or a site that will not load.',
            "We back up first, find what broke (usually a plugin, theme or PHP update), fix it, and bring the site back.\n\nCovers one error. If the cause turns out to be a hack, we tell you: you can pay the difference for the Hacked Website Clean-Up, or get a full refund.",
            "Cause found and fixed\nA backup before we change anything\nA short note on what happened"],
        ['php-upgrade-compatibility-fix', 'PHP 8 Upgrade and Compatibility Fix', 'any', 3, 5999, 149,
            'Move your website to a supported PHP 8 version and fix what breaks.',
            "Old PHP versions no longer get security fixes, and many hosts are switching them off. We test your site on a current PHP 8 version, fix the errors and warnings in the theme, plugins or custom code, and then switch it over.\n\nFor WordPress and PHP sites. Very old custom code (written for PHP 5) may need a quote first.",
            "Site tested on PHP 8 first\nErrors in theme, plugins and custom code fixed\nPlugins and theme updated\nSwitched over once it all works"],
        ['google-search-console-analytics-setup', 'Google Search Console and Analytics Setup', 'any', 2, 2999, 79,
            'Connect your website to Google Search Console and Google Analytics 4, ready to use.',
            "We verify your site in Google Search Console, submit the sitemap, set up Google Analytics 4 with the main events (contact form sent, phone and WhatsApp clicks), and give you access to both.\n\nBoth Google tools are free; you pay only for the setup.",
            "Search Console verified and sitemap submitted\nGoogle Analytics 4 installed\nContact form, phone and WhatsApp clicks tracked\nAccess shared with you"],
        ['wordpress-updates-and-backup', 'WordPress Updates and Backup', 'wordpress', 2, 2499, 69,
            'A one-time safe update of WordPress, your theme and plugins, with a full backup first.',
            "We back up the whole site, update WordPress, the theme and every plugin, check the main pages still work, and fix small problems the updates cause.\n\nFor regular updates, ask about our monthly maintenance plan.",
            "Full backup before updating\nWordPress, theme and plugins updated\nMain pages and forms checked\nSmall update problems fixed"],
        ['small-website-fix', 'Small Website Fix (up to 2 hours)', 'any', 2, 3999, 99,
            'Up to two hours of work on a small change or fix to your website.',
            "For small jobs: a layout or text change, a broken form or link, a new section on a page, a plugin set-up. Describe it at checkout; if it will take longer than two hours, we tell you before starting and you can get a refund.\n\nWorks on WordPress, HTML, PHP and Yii2 sites.",
            "Up to 2 hours of work\nWordPress, HTML, PHP or Yii2\nWe confirm the job fits before starting"],
    ];

    public function safeUp(): void
    {
        // Our own catalogue: a copy sold to another business (PORTAL_ONLY) gets none of it.
        if (\common\components\PortalOnly::enabled()) {
            return;
        }
        $existing = (new \yii\db\Query())->select('slug')->from('{{%product}}')->column($this->db);
        $order = 100;
        foreach (self::SERVICES as [$slug, $name, $technology, $days, $inr, $usd, $summary, $description, $features]) {
            $order += 10;
            if (in_array($slug, $existing, true)) {
                continue;
            }
            $this->insert('{{%product}}', [
                'name' => $name,
                'slug' => $slug,
                'technology' => $technology,
                'kind' => 'service',
                'summary' => $summary,
                'description' => $description,
                'features' => $features,
                'delivery_days' => $days,
                'price_single_inr' => $inr,
                'price_single_usd' => $usd,
                'status' => 0, // Draft: staff publish once happy with the price and wording
                'sort_order' => $order,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }
    }

    public function safeDown(): void
    {
        // Only drafts never ordered: a published or sold service is staff's now.
        $slugs = array_column(self::SERVICES, 0);
        $this->delete('{{%product}}', ['and', ['slug' => $slugs, 'kind' => 'service', 'status' => 0], ['not in', 'id', (new \yii\db\Query())->select('product_id')->from('{{%product_order}}')]]);
    }
}
