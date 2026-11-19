<?php

declare(strict_types=1);

namespace common\components;

use Yii;
use yii\base\ActionEvent;
use yii\web\NotFoundHttpException;

/**
 * Portal-only mode (PORTAL_ONLY=true in .env), for a copy of this software sold to another business: only sign-in,
 * the customer portal and the admin billing work. The marketing pages, blog and store are switched off, because
 * the business has its own website. Off on our own site.
 */
final class PortalOnly
{
    /** Public website controllers that do not exist in a portal-only copy. */
    /** feedback: customer reviews become testimonials on our website. */
    private const CLOSED_FRONTEND = ['blog', 'store', 'purchase', 'sitemap', 'feedback'];
    /** Pages of SiteController that are our marketing and policy pages; site/index goes to sign-in instead. */
    private const CLOSED_SITE = ['contact', 'audit', 'about', 'hosting', 'terms', 'privacy', 'refund-policy', 'licence', 'shipping-policy'];
    /** Admin sections that manage the website rather than billing. */
    public const CLOSED_BACKEND = ['blog-post', 'blog-category', 'blog-comment', 'blog-media', 'blog-subscriber', 'seo-page', 'testimonial', 'audit-request', 'lead', 'product', 'product-order'];

    public static function enabled(): bool
    {
        return (bool) (Yii::$app->params['portalOnly'] ?? false);
    }

    /** Frontend beforeAction: closed pages are not found, the home page is the sign-in, and nothing is indexed. */
    public static function check(ActionEvent $event): void
    {
        if (!self::enabled()) {
            return;
        }
        // A billing portal is not for search engines.
        Yii::$app->response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        $controller = $event->action->controller->id;
        $action = $event->action->id;
        if ($controller === 'site' && $action === 'index') {
            Yii::$app->response->redirect(Yii::$app->user->isGuest ? ['/site/login'] : ['/dashboard/index']);
            $event->isValid = false;

            return;
        }
        if (in_array($controller, self::CLOSED_FRONTEND, true) || ($controller === 'site' && in_array($action, self::CLOSED_SITE, true))) {
            throw new NotFoundHttpException('Page not found.');
        }
    }

    /** Where a policy lives in portal-only mode: the business's own page (Site Settings), or null when none is set. */
    public static function policyUrl(string $policy): ?string
    {
        $url = (string) CustomYii::getSetting($policy . '_url');

        return $url !== '' ? $url : null;
    }
}
