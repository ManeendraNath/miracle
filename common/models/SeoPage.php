<?php

declare(strict_types=1);

namespace common\models;

/**
 * The title and description search engines show for one fixed page of the website, set by staff. Empty = the page's
 * own built-in text. Blog posts have their own (on the post form).
 */
class SeoPage extends base\SeoPage
{
    /**
     * Actions of the public SiteController that are not pages for searchers: kept out of the sitemap and of this
     * list, and marked noindex. Every other action there is a public page, found by itself: a page added later is in
     * the sitemap and on the admin SEO page with no list to update. An action added there that is NOT a page
     * (a form handler, a JSON endpoint, a thank-you page) must be added here.
     */
    public const PRIVATE_ACTIONS = ['login', 'logout', 'signup', 'request-password-reset', 'reset-password', 'verify-email', 'resend-verification-email', 'csp-report', 'error', 'captcha'];

    /** @var array<string, string>|null worked out once per request (it only changes with the code) */
    private static ?array $_pages = null;

    /** Clearer names for some pages; others are named from their address (refund-policy → Refund policy). */
    private const LABELS = [
        'site/index' => 'Home',
        'site/hosting' => 'Hosting, domains and maintenance',
        'site/audit' => 'Upgrade your existing website',
        'site/contact' => 'Contact / get a quote',
        'site/terms' => 'Terms & conditions',
        'site/licence' => 'Licence terms for themes, plugins & software',
        'blog/index' => 'Blog (list of posts)',
        'store/index' => 'Themes & plugins store',
        'store/services' => 'Fixed-price services',
        'store/plans' => 'Website care plans',
    ];

    /**
     * The website's fixed public pages, route => name staff see, in the order they are written in SiteController
     * (home first), then the blog list.
     * @return array<string, string>
     */
    public static function pages(): array
    {
        if (self::$_pages !== null) {
            return self::$_pages;
        }
        $routes = [];
        foreach ((new \ReflectionClass(\frontend\controllers\SiteController::class))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (preg_match('/^action([A-Z]\w*)$/', $method->name, $m) === 1 && $method->getNumberOfRequiredParameters() === 0) {
                $id = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $m[1]));
                if (!in_array($id, self::PRIVATE_ACTIONS, true)) {
                    $routes[] = 'site/' . $id;
                }
            }
        }
        usort($routes, static fn(string $a, string $b): int => ($b === 'site/index') <=> ($a === 'site/index'));
        $routes[] = 'store/index';
        $routes[] = 'store/services';
        $routes[] = 'store/plans';
        $routes[] = 'blog/index';
        $pages = [];
        foreach ($routes as $route) {
            $pages[$route] = self::LABELS[$route] ?? ucfirst(str_replace('-', ' ', substr($route, 5)));
        }

        return self::$_pages = $pages;
    }

    #[\Override]
    public function rules(): array
    {
        return array_merge([
            [['title', 'description'], 'trim'],
            [['route'], 'in', 'range' => array_keys(self::pages())],
        ], parent::rules());
    }

    #[\Override]
    public function attributeLabels(): array
    {
        return array_merge(parent::attributeLabels(), ['title' => 'Title in search results', 'description' => 'Description in search results']);
    }

    #[\Override]
    public function beforeValidate(): bool
    {
        $this->updated_at = time();

        return parent::beforeValidate();
    }

    public static function forRoute(string $route): ?self
    {
        return isset(self::pages()[$route]) ? self::findOne(['route' => $route]) : null;
    }
}
