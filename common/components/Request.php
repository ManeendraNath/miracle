<?php

namespace common\components;

class Request extends \yii\web\Request
{
    public $web;
    public $adminUrl;

    public function getBaseUrl()
    {
        if ($this->isGii()) {
            return '';
        }

        if (empty($this->web)) {
            return parent::getBaseUrl() . $this->adminUrl;
        } else {
            return str_replace($this->web, '', parent::getBaseUrl()) . $this->adminUrl;
        }
    }

    public function resolvePathInfo()
    {
        // 💡 THE CURE: If a request is for Gii, bypass custom routing rules completely
        // and return parent::resolvePathInfo() so deep routes like /gii/model work perfectly!
        if ($this->isGii()) {
            return parent::resolvePathInfo();
        }

        if ($this->getUrl() === $this->adminUrl) {
            return '';
        } else {
            return parent::resolvePathInfo();
        }
    }

    /**
     * Gii only exists in dev; match the path prefix, not "/gii" anywhere in the URL.
     */
    private function isGii(): bool
    {
        return YII_ENV_DEV && str_starts_with((string) parse_url((string) $this->getUrl(), PHP_URL_PATH), '/gii');
    }
}
