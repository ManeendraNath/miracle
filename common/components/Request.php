<?php

namespace common\components;

class Request extends \yii\web\Request {
    public $web;
    public $adminUrl;

    public function getBaseUrl(){
        if (strpos($this->getUrl() ?? '', '/gii') !== false) {
            return '';
        }

        if(empty($this->web)) {
            return parent::getBaseUrl() . $this->adminUrl;
        } else {
            return str_replace($this->web, "", parent::getBaseUrl()) . $this->adminUrl;
        }
    }

    public function resolvePathInfo(){
        // 💡 THE CURE: If a request is for Gii, bypass custom routing rules completely
        // and return parent::resolvePathInfo() so deep routes like /gii/model work perfectly!
        if (strpos($this->getUrl(), '/gii') !== false) {
            return parent::resolvePathInfo();
        }

        if($this->getUrl() === $this->adminUrl){
            return "";
        }else{
            return parent::resolvePathInfo();
        }
    }
}
