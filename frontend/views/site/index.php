<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Testimonial[] $testimonials */

use common\components\CustomYii;
use yii\bootstrap5\Html;

$this->title = 'Website, Web Application and Shopping Cart Development | Miracle Web Technologies';
$this->params['bare'] = true;
$this->params['meta_description'] = 'Custom shopping carts, web applications and business websites for clients in India and abroad, plus upgrades to the site you already have. Get a written quote.';

$address = array_filter(array_map(static fn($l): string => trim((string) $l, ' ,'), [
    CustomYii::getSetting('address_line_1'),
    CustomYii::getSetting('address_line_2'),
    CustomYii::getSetting('address_line_3'),
]));
$phone = (string) CustomYii::getSetting('mobile_1');
$email = (string) CustomYii::getSetting('email_1');

$services = [
    [
        'icon' => 'fa-cogs',
        'title' => 'Web applications',
        'text' => 'Customer portals, billing systems, dashboards and internal tools that replace spreadsheets and manual work.',
        'link' => 'Discuss your application',
        'url' => ['site/contact', 'subject' => 'Project enquiry: web application'],
    ],
    [
        'icon' => 'fa-laptop-code',
        'title' => 'Business websites',
        'text' => 'Websites built to your brief that work well on phones, tablets and desktops.',
        'link' => 'Discuss your website',
        'url' => ['site/contact', 'subject' => 'Project enquiry: business website'],
    ],
    [
        'icon' => 'fa-sync-alt',
        'title' => 'Upgrades to existing websites',
        'text' => 'A faster, safer and better-looking version of the site you already have, or an older web application brought up to date.',
        'link' => 'Upgrade my website',
        'url' => ['site/audit'],
    ],
];

$projects = [
    [
        'name' => 'NMP Ayurveda',
        'url' => 'https://nmpamrit.com',
        'image' => 'nmp-ayurveda.jpg',
        'type' => 'Shopping cart',
        'text' => 'An online store for Ayurvedic health supplements, with products to browse by category and health concern, a cart, checkout and customer accounts.',
    ],
    [
        'name' => 'AS International',
        'url' => 'https://asintl.net.in',
        'image' => 'as-international.jpg',
        'type' => 'Business website',
        'text' => 'A website for a packaging and label company, showing its portfolio and bringing in quote requests.',
    ],
    [
        'name' => 'RM Star Retouching',
        'url' => 'https://rmstarretouchingcompany.com',
        'image' => 'rm-star-retouching.jpg',
        'type' => 'Business website',
        'text' => 'A website for a photo retouching company serving clients around the world, presenting its many services.',
    ],
];

$technologies = ['PHP', 'Yii', 'MySQL', 'PostgreSQL', 'Docker'];

$steps = [
    'Tell us what you need' => 'Send a short description, or the address of your current site. A rough idea is enough to start.',
    'Get a written quote' => 'The quote sets out the scope and the price, so you know what you are paying for before work begins.',
    'We build, you review' => 'We share our progress for your feedback. Timing depends on receiving your content and approvals on time.',
    'Launch and handover' => 'The finished work is yours once its invoices are paid in full. We can host and maintain it if you want us to.',
];

$questions = [
    'Who owns the finished work?' => 'You do, once its invoices are paid in full. We may show completed work in our portfolio unless you ask us not to.',
    'What if I change my mind?' => 'An advance is refunded in full if you cancel before work begins. After that, we keep the amount for work already completed and refund any balance.',
    'How do we stay in touch?' => 'By email and phone, and through support tickets in your customer portal.',
    'Can you look after it afterwards?' => 'Yes. We offer website maintenance, hosting and domain registration, so one team can look after everything.',
];
?>
<div class="site-index">

    <section class="mw-glow mw-hero">
        <div class="container">
            <div class="row g-4 g-lg-5 align-items-center">
                <div class="col-lg-7">
                    <p class="mw-eyebrow">Web development company in India, for clients in India and abroad</p>
                    <h1 class="mw-h1 mb-4">Shopping carts, web applications and websites, <span class="mw-accent">cut to fit your business.</span></h1>
                    <p class="mw-lead mb-4">Send us your idea, or the site you already have. We'll reply with a written quote, build it to your brief, and hand it over once it is paid for.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <?= Html::a('Get a project quote', ['site/contact', 'subject' => 'Project enquiry'], ['class' => 'btn btn-mw-bright btn-lg']) ?>
                        <?= Html::a('Upgrade my website', ['site/audit'], ['class' => 'btn btn-outline-light btn-lg']) ?>
                    </div>
                </div>
                <div class="col-lg-5">
                    <figure class="mw-portal mw-stage">
                        <?= Html::img('@web/images/gem.svg', ['alt' => '', 'class' => 'mw-gem', 'width' => 640, 'height' => 560]) ?>
                        <div class="mw-portal-card">
                            <div class="mw-browser-bar"><span></span><span></span><span></span><div class="mw-browser-url">yourstore.com/checkout</div></div>
                            <div class="mw-portal-row">
                                <div><span class="mw-portal-name">Leather notebook</span><span class="mw-portal-meta">Quantity 2</span></div>
                                <div class="mw-portal-date">$48.00</div>
                            </div>
                            <div class="mw-portal-row">
                                <div><span class="mw-portal-name">Brass pen</span><span class="mw-portal-meta">Quantity 1</span></div>
                                <div class="mw-portal-date">$32.00</div>
                            </div>
                            <div class="mw-portal-invoice">
                                <div><span class="mw-portal-name">Order total: $80.00</span></div>
                                <div class="mw-portal-pay">Place order</div>
                            </div>
                        </div>
                        <div class="mw-float">
                            <span class="mw-float-status d-block">New order received</span>
                            <span class="mw-portal-name d-block">$80, paid online</span>
                            <span class="mw-portal-meta d-block">Your store's order dashboard</span>
                        </div>
                        <figcaption>An illustration with example details, not a client project.</figcaption>
                    </figure>
                </div>
            </div>
            <ul class="mw-facts">
                <li><i class="fas fa-check" aria-hidden="true"></i>A written quote before any work starts</li>
                <li><i class="fas fa-check" aria-hidden="true"></i>Your advance back in full if you cancel before work begins</li>
                <li><i class="fas fa-check" aria-hidden="true"></i>The finished work is yours once it is paid for</li>
            </ul>
        </div>
    </section>

    <section id="services" class="mw-section mw-section-soft">
        <div class="container">
            <h2 class="mw-h2">What we build</h2>
            <p class="mw-lead mt-3 mb-5">Custom work, quoted for your project. Start something new or improve what you already have.</p>
            <div class="row g-4">
                <div class="col-12">
                    <div class="mw-card mw-card-lead">
                        <div class="row g-4 align-items-center">
                            <div class="col-lg-6">
                                <div class="mw-icon"><i class="fas fa-shopping-cart" aria-hidden="true"></i></div>
                                <h3>Shopping carts and online stores</h3>
                                <p>A store set up around how you sell, whether your customers are next door or in another country.</p>
                                <?= Html::a('Discuss your store', ['site/contact', 'subject' => 'Project enquiry: shopping cart'], ['class' => 'fw-semibold']) ?>
                            </div>
                            <div class="col-lg-6">
                                <ul class="mw-chips">
                                    <li>Product catalogues</li>
                                    <li>Cart and checkout</li>
                                    <li>Payment gateway integration</li>
                                    <li>Customer accounts</li>
                                    <li>Order management</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <?php foreach ($services as $service): ?>
                    <div class="col-lg-4">
                        <div class="mw-card">
                            <div class="mw-icon"><i class="fas <?= $service['icon'] ?>" aria-hidden="true"></i></div>
                            <h3><?= Html::encode($service['title']) ?></h3>
                            <p><?= Html::encode($service['text']) ?></p>
                            <?= Html::a(Html::encode($service['link']), $service['url'], ['class' => 'fw-semibold']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="mw-tech">
                <h3>Technologies we work with</h3>
                <ul class="mw-chips">
                    <?php foreach ($technologies as $technology): ?>
                        <li><?= Html::encode($technology) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </section>

    <section id="work" class="mw-section">
        <div class="container">
            <h2 class="mw-h2 mb-5">Work we have delivered</h2>
            <div class="row g-4">
                <?php foreach ($projects as $project): ?>
                    <div class="col-lg-4">
                        <article class="mw-card mw-project">
                            <?= Html::img('@web/images/projects/' . $project['image'], ['alt' => 'Home page of ' . $project['name'], 'width' => 960, 'height' => 600, 'loading' => 'lazy']) ?>
                            <div class="mw-project-body">
                                <p class="mw-eyebrow mb-1"><?= Html::encode($project['type']) ?></p>
                                <h3><?= Html::encode($project['name']) ?></h3>
                                <p><?= Html::encode($project['text']) ?></p>
                                <?= Html::a('Visit ' . Html::encode($project['name']) . ' <i class="fas fa-external-link-alt small" aria-hidden="true"></i>', $project['url'], ['class' => 'fw-semibold', 'target' => '_blank', 'rel' => 'noopener']) ?>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php if ($testimonials): ?>
        <section class="mw-section mw-section-soft">
            <div class="container">
                <h2 class="mw-h2 mb-5">What our clients say</h2>
                <div class="row g-4">
                    <?php foreach ($testimonials as $testimonial): ?>
                        <div class="col-lg-4">
                            <figure class="mw-card mb-0">
                                <blockquote class="mb-3"><?= Html::encode($testimonial->quote) ?></blockquote>
                                <figcaption>
                                    <span class="mw-portal-name d-block"><?= Html::encode($testimonial->client_name) ?></span>
                                    <span class="mw-portal-meta d-block"><?= Html::encode($testimonial->byline()) ?></span>
                                </figcaption>
                            </figure>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section class="mw-section mw-section-ink">
        <div class="container">
            <div class="row g-4 g-lg-5 align-items-center">
                <div class="col-lg-6">
                    <h2 class="mw-h2">We run our own business on software we built</h2>
                    <p class="mw-lead mt-3 mb-4">Our customer portal is the kind of web application we build for clients. It handles:</p>
                    <ul class="mw-checks">
                        <li><i class="fas fa-check" aria-hidden="true"></i>Services and renewal dates</li>
                        <li><i class="fas fa-check" aria-hidden="true"></i>Invoices, online payments and receipts</li>
                        <li><i class="fas fa-check" aria-hidden="true"></i>Support tickets and notifications</li>
                    </ul>
                </div>
                <div class="col-lg-6">
                    <figure class="mw-portal">
                        <div class="mw-portal-card">
                            <div class="mw-portal-title">Your services</div>
                            <div class="mw-portal-row">
                                <div><span class="mw-portal-name">yourbusiness.com</span><span class="mw-portal-meta">Domain</span></div>
                                <div class="mw-portal-date">Renews 14 Mar</div>
                            </div>
                            <div class="mw-portal-row">
                                <div><span class="mw-portal-name">Website care</span><span class="mw-portal-meta">Maintenance</span></div>
                                <div class="mw-portal-date">Renews 19 Aug</div>
                            </div>
                            <div class="mw-portal-invoice">
                                <div><span class="mw-portal-name d-block">Next invoice</span><span class="mw-portal-meta">View, pay and download receipts</span></div>
                                <div class="mw-portal-pay">Pay invoice</div>
                            </div>
                        </div>
                        <figcaption>An illustration of our customer portal, with example details.</figcaption>
                    </figure>
                </div>
            </div>
        </div>
    </section>

    <section id="process" class="mw-section">
        <div class="container">
            <h2 class="mw-h2 mb-5">How a project works</h2>
            <ol class="mw-steps">
                <?php $number = 0; ?>
                <?php foreach ($steps as $title => $text): ?>
                    <li class="mw-step">
                        <div class="mw-step-number" aria-hidden="true"><?= ++$number ?></div>
                        <h3><?= Html::encode($title) ?></h3>
                        <p><?= Html::encode($text) ?></p>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
    </section>

    <section class="mw-section mw-section-soft">
        <div class="container">
            <h2 class="mw-h2 mb-5">Wherever your business is</h2>
            <div class="row g-4 mb-5">
                <div class="col-md-6">
                    <div class="mw-card">
                        <div class="mw-icon"><i class="fas fa-globe" aria-hidden="true"></i></div>
                        <h3>Clients abroad</h3>
                        <ul class="mw-checks">
                            <li><i class="fas fa-check" aria-hidden="true"></i>Everything handled online, in English</li>
                            <li><i class="fas fa-check" aria-hidden="true"></i>Pay online by international card through Razorpay</li>
                            <li><i class="fas fa-check" aria-hidden="true"></i>Stay in touch by email and phone</li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mw-card">
                        <div class="mw-icon"><i class="fas fa-map-marker-alt" aria-hidden="true"></i></div>
                        <h3>Clients in India</h3>
                        <ul class="mw-checks">
                            <li><i class="fas fa-check" aria-hidden="true"></i>Based in Delhi</li>
                            <li><i class="fas fa-check" aria-hidden="true"></i>Local payment methods, including UPI</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="row g-4 g-lg-5">
                <div class="col-lg-7 mw-faq">
                    <h2 class="h4 fw-bold mb-3">Common questions</h2>
                    <?php foreach ($questions as $question => $answer): ?>
                        <details>
                            <summary><?= Html::encode($question) ?></summary>
                            <p><?= Html::encode($answer) ?></p>
                        </details>
                    <?php endforeach; ?>
                </div>
                <div class="col-lg-5">
                    <div class="mw-panel">
                        <h2 class="h4 fw-bold mb-3">Talk to us</h2>
                        <ul class="mw-contact-list">
                            <?php if ($email !== ''): ?>
                                <li><i class="fas fa-envelope fa-fw" aria-hidden="true"></i><?= Html::mailto(Html::encode($email), $email) ?></li>
                            <?php endif; ?>
                            <?php if ($phone !== ''): ?>
                                <li><i class="fas fa-phone-alt fa-fw" aria-hidden="true"></i><?= Html::a(Html::encode(CustomYii::formatPhone($phone)), 'tel:+' . $phone) ?></li>
                            <?php endif; ?>
                            <?php if ($address): ?>
                                <li><i class="fas fa-map-marker-alt fa-fw" aria-hidden="true"></i><span><?= implode('<br>', array_map([Html::class, 'encode'], $address)) ?><br>India</span></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <p class="mt-4 mb-0">Need somewhere to run your site? <?= Html::a('See hosting and domains', ['site/hosting']) ?>.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="mw-band">
        <div class="container d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h2 class="mw-h2 mb-2">Have a project in mind?</h2>
                <p class="mb-0">Tell us what you want to build or improve, and we'll reply with the next steps.</p>
            </div>
            <?= Html::a('Get a project quote', ['site/contact', 'subject' => 'Project enquiry'], ['class' => 'btn btn-light btn-lg fw-semibold']) ?>
        </div>
    </section>
</div>
