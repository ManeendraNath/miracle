<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * The blog's first four posts, added as Drafts: staff read them, add a cover image and publish from the admin.
 * Can be run again: a post or category whose slug already exists is left as it is.
 */
final class m261129_100000_add_first_blog_posts extends Migration
{
    private const CATEGORIES = [
        'guides' => 'Guides for business owners',
        'working-with-a-developer' => 'Working with a developer',
    ];

    public function safeUp(): void
    {
        // Our own posts: a copy sold to another business (PORTAL_ONLY) gets none of them.
        if (\common\components\PortalOnly::enabled()) {
            return;
        }
        $now = time();
        $categoryIds = [];
        foreach (self::CATEGORIES as $slug => $name) {
            $id = $this->db->createCommand('SELECT id FROM {{%blog_category}} WHERE slug = :slug', [':slug' => $slug])->queryScalar();
            if ($id === false) {
                $this->insert('{{%blog_category}}', ['name' => $name, 'slug' => $slug, 'created_at' => $now, 'updated_at' => $now]);
                $id = $this->db->getLastInsertID();
            }
            $categoryIds[$slug] = (int) $id;
        }
        foreach (self::posts() as $post) {
            $exists = $this->db->createCommand('SELECT 1 FROM {{%blog_post}} WHERE slug = :slug', [':slug' => $post['slug']])->queryScalar();
            if ($exists !== false) {
                continue;
            }
            $this->insert('{{%blog_post}}', [
                'category_id' => $categoryIds[$post['category']],
                'title' => $post['title'],
                'slug' => $post['slug'],
                'summary' => $post['summary'],
                'body' => $post['body'],
                'seo_title' => $post['seo_title'],
                'status' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function safeDown(): void
    {
        // Only drafts nobody has published; categories are kept if any post uses them.
        $this->delete('{{%blog_post}}', ['slug' => array_column(self::posts(), 'slug'), 'status' => 0]);
        foreach (array_keys(self::CATEGORIES) as $slug) {
            $id = $this->db->createCommand('SELECT id FROM {{%blog_category}} WHERE slug = :slug', [':slug' => $slug])->queryScalar();
            if ($id !== false && $this->db->createCommand('SELECT COUNT(*) FROM {{%blog_post}} WHERE category_id = :id', [':id' => $id])->queryScalar() == 0) {
                $this->delete('{{%blog_category}}', ['id' => $id]);
            }
        }
    }

    /** @return list<array{slug: string, category: string, title: string, seo_title: string, summary: string, body: string}> */
    private static function posts(): array
    {
        return [
            [
                'slug' => 'php-8-2-end-of-life-31-december-2026',
                'category' => 'guides',
                'title' => 'PHP 8.2 stops getting security fixes on 31 December 2026: is your website ready?',
                'seo_title' => 'PHP 8.2 end of life on 31 December 2026: what to do',
                'summary' => 'After 31 December 2026, PHP 8.2 gets no more security fixes. How to check which PHP version your website runs, what can break when you upgrade, and a safe way to move to PHP 8.4 or 8.5.',
                'body' => <<<'HTML'
<p>Most business websites, online shops and customer portals run on PHP. Each PHP version is supported for four years: two years of bug fixes, then two years of security fixes only. After that, newly found security holes in that version are no longer fixed.</p>
<p><strong>PHP 8.2 reaches that point on 31 December 2026.</strong> PHP 8.1 already did on 31 December 2025. If your website runs on either, it is time to plan the upgrade.</p>

<h2>Where each version stands</h2>
<ul>
<li><strong>PHP 8.1:</strong> no security fixes since 31 December 2025.</li>
<li><strong>PHP 8.2:</strong> security fixes until 31 December 2026.</li>
<li><strong>PHP 8.3:</strong> security fixes until 31 December 2027.</li>
<li><strong>PHP 8.4:</strong> security fixes until 31 December 2028.</li>
<li><strong>PHP 8.5:</strong> security fixes until 31 December 2029.</li>
</ul>
<p>Anything older (PHP 7.x, 5.x) has been unsupported for years. If your site still runs on one of these, it is the most urgent case of all.</p>

<h2>Why it matters even if the site "works fine"</h2>
<p>An old PHP version does not stop working on the last day. The risk is quieter: when a new security problem is found, it is fixed in the supported versions only. Your site stays exposed, and attackers scan the web for exactly these sites. Many hosting companies also remove old PHP versions from their servers over time, and the upgrade then happens on their schedule instead of yours.</p>

<h2>How to check your PHP version</h2>
<ul>
<li><strong>cPanel hosting:</strong> open <em>Select PHP Version</em> or <em>MultiPHP Manager</em>. The current version is shown for each domain.</li>
<li><strong>WordPress:</strong> go to <em>Tools → Site Health → Info → Server</em>.</li>
<li><strong>Not sure?</strong> Ask your developer or hosting provider. It is a one-minute question for them.</li>
</ul>

<h2>What can break when you upgrade</h2>
<p>Newer PHP versions remove or tighten things that older code relied on. The usual problems are:</p>
<ul>
<li><strong>Old plugins, themes or extensions</strong> that were never updated by their authors.</li>
<li><strong>Custom code written years ago,</strong> for example code that adds properties to objects on the fly (deprecated since PHP 8.2) or uses functions that no longer exist.</li>
<li><strong>Payment and shipping integrations</strong> built on old software libraries.</li>
</ul>
<p>Often the fix is small. But you want to find these problems on a copy of the site, not on the live site in front of your customers.</p>

<h2>A safe way to upgrade</h2>
<ol>
<li><strong>Take a full backup</strong> of the files and the database.</li>
<li><strong>Make a copy of the site</strong> (a staging site) on the new PHP version.</li>
<li><strong>Update the CMS, plugins and libraries</strong> on the copy first.</li>
<li><strong>Test what earns you money:</strong> contact forms, checkout, payments, customer login, emails.</li>
<li><strong>Read the error log</strong> for warnings, even when pages look fine.</li>
<li><strong>Switch the live site</strong>, keeping the old version available for a quick rollback.</li>
<li><strong>Watch the logs</strong> for a few days after the switch.</li>
</ol>
<p>Moving to PHP 8.4 or 8.5 now buys you two or three more years before the next upgrade. Newer versions are usually a little faster too.</p>

<h2>Need a hand?</h2>
<p>Our own servers already run PHP 8.5. We upgrade older websites and applications to current PHP versions, including custom code that nobody has touched in years. Tell us what your site runs on and we will tell you what the upgrade involves before any work starts.</p>
HTML,
            ],
            [
                'slug' => 'european-accessibility-act-websites-selling-to-eu',
                'category' => 'guides',
                'title' => 'Selling online to customers in the EU? The European Accessibility Act now applies to your website',
                'seo_title' => 'European Accessibility Act: what it means for your online shop',
                'summary' => 'Since 28 June 2025, online shops and many digital services sold to consumers in the EU must be accessible to people with disabilities. Who it covers, the standard it points to, and a practical checklist.',
                'body' => <<<'HTML'
<p>The European Accessibility Act (Directive (EU) 2019/882) has applied since <strong>28 June 2025</strong>. It requires a range of products and services sold to consumers in the European Union to be accessible to people with disabilities. Online shopping is one of them.</p>
<p>If you sell to customers in the EU, this affects your website, even if your company is based outside Europe.</p>
<p><em>This article is a practical overview, not legal advice. For your specific case, check with a lawyer or the authority in the countries where you sell.</em></p>

<h2>Who it covers</h2>
<ul>
<li><strong>E-commerce:</strong> websites and apps that sell products or services to consumers, including product pages, the basket, checkout and account pages.</li>
<li><strong>Other services named in the Act:</strong> including consumer banking, e-books, electronic communications, and ticketing for passenger transport.</li>
<li><strong>Businesses outside the EU</strong> that offer these services to consumers in the EU.</li>
</ul>
<p>There is an exemption for <strong>microenterprises providing services</strong>: fewer than 10 employees and an annual turnover or balance sheet total of no more than €2 million. Business-to-business sales are generally outside the Act.</p>

<h2>What "accessible" means in practice</h2>
<p>The Act describes requirements in general terms. The European standard it relies on, EN 301 549, follows the <strong>Web Content Accessibility Guidelines (WCAG) at level AA</strong> for web content: WCAG 2.1 in the version used for years, WCAG 2.2 in the 2026 update (version 4.1.1). Aiming for WCAG 2.2 AA covers both, and is the practical target for most websites.</p>
<p>You are also expected to explain how your service meets the requirements, in your terms and conditions or in an accessibility statement on the website.</p>

<h2>A checklist for your online shop</h2>
<ul>
<li><strong>Keyboard:</strong> can someone browse, add to the basket and pay using only the keyboard? Is it always visible where the focus is?</li>
<li><strong>Images:</strong> do product photos and icons that carry meaning have a text alternative?</li>
<li><strong>Contrast:</strong> is the text readable against its background, including grey text and text on photos?</li>
<li><strong>Forms:</strong> does every field have a visible label? Do error messages say what went wrong and how to fix it?</li>
<li><strong>Zoom:</strong> does the page still work when the text is enlarged to 200%?</li>
<li><strong>Video:</strong> do product videos have captions?</li>
<li><strong>Checkout and payment pages:</strong> including any payment window from a third party. Ask your payment provider about their accessibility.</li>
<li><strong>Time limits and pop-ups:</strong> can people close them, and do they get enough time?</li>
</ul>

<h2>Where to start</h2>
<ol>
<li><strong>Test the main journey first:</strong> home page, a product, the basket, checkout, and your account pages.</li>
<li><strong>Use automated checks</strong> (such as the free Lighthouse report built into Chrome). They catch a part of the problems, not all.</li>
<li><strong>Test by hand</strong> with the keyboard and a screen reader for the rest.</li>
<li><strong>Fix, then publish</strong> an accessibility statement saying what you meet and how people can contact you.</li>
</ol>
<p>Accessibility work usually improves the site for everyone: clearer forms, readable text and pages that work on any device. Fewer abandoned checkouts is a welcome side effect.</p>

<h2>Need a hand?</h2>
<p>We review online shops against WCAG 2.2 AA and fix what we find, from contrast and labels to keyboard access in the checkout. Ask us for an audit of your site.</p>
HTML,
            ],
            [
                'slug' => 'ai-search-small-business-website',
                'category' => 'guides',
                'title' => 'When Google answers before the click: making your business website useful to AI search',
                'seo_title' => 'AI search and your business website: what still works',
                'summary' => 'Google\'s AI Overviews and AI Mode, ChatGPT and other assistants now answer many questions directly. What that changes for a small business website, and the practical things that help you get mentioned and visited.',
                'body' => <<<'HTML'
<p>Search has changed. For many questions, Google now shows an AI-written answer at the top of the page, and AI Mode turns search into a conversation. People also ask ChatGPT, Gemini and other assistants directly. These answers usually name and link the websites they draw from.</p>
<p>For a small business, the question is simple: when someone asks about what you do, will your website be one of the sources?</p>

<h2>No special trick is needed</h2>
<p>Google has said that its AI features rely on the same things as normal search: a page that can be crawled and indexed, and content that is helpful to people. There is no separate file or markup that makes AI choose you. That is good news, because the work that helps is the work that also helps your customers.</p>

<h2>What helps</h2>
<ul>
<li><strong>Answer real questions on your pages.</strong> Write down the questions customers ask you on the phone or by email, and answer them clearly on the site: what you offer, for whom, where, for how much, and how long it takes.</li>
<li><strong>Put the facts in text, not only in images.</strong> Prices, service areas, opening hours and contact details inside a picture or a PDF are easy to miss.</li>
<li><strong>Keep your business details the same everywhere:</strong> your website, Google Business Profile and directories. AI answers often combine these sources.</li>
<li><strong>Show who is behind the business.</strong> Real names, experience, photos of your work and real customer reviews give a page weight that generic text does not have.</li>
<li><strong>Write from experience.</strong> AI summaries are built on what is already out there. A page that only repeats it has little reason to be cited; a page with your own examples, numbers and opinions does.</li>
<li><strong>Add structured data</strong> (schema.org) for your organisation, local business, products and articles, so machines read the facts correctly.</li>
<li><strong>Keep the site fast and working on phones,</strong> with every important page linked from your menus or sitemap.</li>
</ul>

<h2>What to measure now</h2>
<p>Fewer clicks for simple questions is a real effect: if the answer is on the results page, some people do not visit. The visitors who do click are often further along, ready to compare or buy. So look beyond the visitor count:</p>
<ul>
<li>Enquiries, calls and sales from the website.</li>
<li>In Google Search Console, which searches show your pages and which pages get clicks.</li>
<li>Whether assistants mention your business when you ask them about your service and city.</li>
</ul>

<h2>A short plan</h2>
<ol>
<li>List the ten questions customers ask you most.</li>
<li>Check that each one is clearly answered on your website. Write the answer where it is not.</li>
<li>Check your Google Business Profile matches your website.</li>
<li>Add structured data and check it with Google's Rich Results Test.</li>
<li>Repeat every few months: services, prices and questions change.</li>
</ol>

<h2>Need a hand?</h2>
<p>We build and improve business websites with clear service pages, structured data and fast loading. Ask us for a free review of your website.</p>
HTML,
            ],
            [
                'slug' => 'hiring-a-web-developer-in-india-from-abroad',
                'category' => 'working-with-a-developer',
                'title' => 'Hiring a web developer in India from abroad: 10 things to agree before work starts',
                'seo_title' => 'Hiring a web developer in India: 10 things to agree first',
                'summary' => 'Working with a development team in India can save money and time, if the basics are agreed in writing first. A checklist from our side of the table: scope, payments, code ownership, accounts, time zones and support after launch.',
                'body' => <<<'HTML'
<p>Many companies in the US, UK, Europe and Australia have their websites and web applications built in India. When it goes well, you get experienced developers, good value and work that continues while you sleep. When it goes badly, it is almost always for the same few reasons, and nearly all of them can be avoided before the first line of code.</p>
<p>Here are the ten things we agree with every client abroad before a project starts. Use them with whoever you hire.</p>

<h2>1. A written scope and quote</h2>
<p>Every page, feature and integration listed, with a fixed price or a clear hourly rate. "An online shop like X" is not a scope. Anything not on the list is a change, with its own price.</p>

<h2>2. Milestones you can see</h2>
<p>Split the work into stages, each with something you can open and click, and pay per stage. You never pay far ahead of what you have seen working.</p>

<h2>3. Currency and payment method</h2>
<p>Agree the currency (US dollars, pounds, euros or rupees), the method (PayPal, card or bank transfer) and who pays the fees and exchange costs. Surprises here sour good projects.</p>

<h2>4. Who owns the code</h2>
<p>The finished work should be yours once it is paid for, in writing. Check what is excluded: paid plugins, themes or the developer's own reusable tools usually come with a licence rather than ownership.</p>

<h2>5. Access to the source code</h2>
<p>Ask for the code in a repository (such as GitHub or Bitbucket) you can access during the project, not a zip file at the end.</p>

<h2>6. Accounts in your name</h2>
<p>Your domain, hosting, payment gateway and email service should be registered to your business, with the developer given access. If you part ways, nothing is held hostage.</p>

<h2>7. Time zones and updates</h2>
<p>India is 3.5 to 5.5 hours ahead of the UK and most of Europe, and 9.5 to 13.5 hours ahead of the US, depending on daylight saving. Agree a regular overlap hour for calls, a weekly written update, and one channel for questions, so nothing gets lost between email, chat and calls.</p>

<h2>8. A test site and acceptance</h2>
<p>New work goes to a test (staging) site first. Agree how long you have to check each stage and what counts as finished, so "done" means the same to both sides.</p>

<h2>9. Your customers' data</h2>
<p>If the site handles personal data of people in the EU or UK, the EU GDPR or UK GDPR may apply. Agree how data is stored, who can access it, and that test copies do not leave real customer data lying around.</p>

<h2>10. Support after launch</h2>
<p>Websites need updates, backups and the occasional fix. Agree up front what happens after launch: a warranty period for bugs, and a maintenance plan or hourly rate for everything else.</p>

<h2>Working with us</h2>
<p>Miracle Web Technologies has built websites, online shops and web applications from Delhi for 11 years, for clients in India and abroad. You get a written quote before work starts, the code is yours once its invoices are paid, and every client has a portal for invoices, payments and support tickets. Clients abroad can pay in US dollars by PayPal. Tell us about your project and we will send you a scope and quote.</p>
HTML,
            ],
        ];
    }
}
