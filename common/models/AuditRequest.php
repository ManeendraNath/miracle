<?php

namespace common\models;

/**
 * This is the model class for table "audit_request".
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $company_url
 * @property string $current_framework
 * @property string $hosting_environment
 * @property string $message
 * @property string|null $status
 * @property int $created_at
 * @property int $updated_at
 */
class AuditRequest extends base\AuditRequest
{

}
