<?php

declare (strict_types=1);

namespace common\models;

use yii\helpers\ArrayHelper;

/**
 * Description of SupportTicketAttachment
 *
 * @author trivedi
 */
class SupportTicketAttachment extends base\SupportTicketAttachment
{

    public $attachment;

    public function rules(): array
    {
        $rules = [
            ['attachment', 'file', 'skipOnEmpty' => true, 'extensions' => [ 'jpg', 'jpeg', 'png', 'pdf', 'zip', 'doc', 'docx', 'xlsx'], 'maxSize' => 10 * 1024 * 1024],
        ];
        return ArrayHelper::merge(parent::rules(), $rules);
    }
}
