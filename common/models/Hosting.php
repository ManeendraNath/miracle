<?php

namespace common\models;

/**
 * This is the model class for table "hosting".
 *
 * @property int $ID
 * @property string $Domain
 * @property string $cPanelIP
 * @property string $cPanelUserName
 * @property string $cPanelPassword
 * @property int|null $Plan 1 : Yearly, 2 : Monthly
 * @property int $PlanPrice
 * @property string $RegisteredDate
 * @property string $nextRenewalDate
 * @property int $UserID
 * @property int $Status 0 : Inactive, 1 : Active, 2 : Expired
 */
class Hosting extends base\Hosting
{

}
