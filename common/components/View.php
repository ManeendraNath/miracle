<?php

declare(strict_types=1);

namespace common\components;

/**
 * Puts this request's CSP nonce on the <script> tags the app itself registers (registerJs, registerJsFile, widgets).
 * Only those: a <script> that got into page content some other way (an XSS) gets none, so the policy blocks it.
 */
class View extends \yii\web\View
{
    private ?string $_nonce = null;

    /** One random value per request, also sent in the Content-Security-Policy header (common/config/main.php). */
    public function getNonce(): string
    {
        return $this->_nonce ??= base64_encode(random_bytes(16));
    }

    protected function renderHeadHtml()
    {
        return $this->withNonce(parent::renderHeadHtml());
    }

    protected function renderBodyBeginHtml()
    {
        return $this->withNonce(parent::renderBodyBeginHtml());
    }

    protected function renderBodyEndHtml($ajaxMode)
    {
        return $this->withNonce(parent::renderBodyEndHtml($ajaxMode));
    }

    private function withNonce(string $html): string
    {
        return (string) preg_replace('/<script(?![^>]*\bnonce=)/i', '<script nonce="' . $this->getNonce() . '"', $html);
    }
}
