<?php

declare(strict_types=1);

namespace backend\tests\unit;

use Codeception\Test\Unit;
use common\models\Hosting;
use Yii;

final class HostingPasswordTest extends Unit
{
    public function testCpanelPasswordIsStoredEncryptedAndNeverMassAssigned(): void
    {
        $tx = Yii::$app->db->beginTransaction();
        try {
            $user = new \common\models\User(['username' => 'pw-' . bin2hex(random_bytes(4)), 'email' => 'pw-' . bin2hex(random_bytes(4)) . '@example.test', 'status' => \common\models\User::getStatusActive(), 'created_at' => time(), 'updated_at' => time()]);
            $user->password = 'PwTestPassword1';
            $this->assertTrue($user->save(), json_encode($user->errors));
            $hosting = new Hosting();
            $hosting->setAttributes(['cpanel_password' => 'raw-from-post', 'new_cpanel_password' => 'S3cret!pass']);
            $this->assertNull($hosting->cpanel_password, 'the stored column is not form-assignable');

            $hosting->setAttributes(['user_id' => $user->id, 'primary_domain' => 'pw-test-' . bin2hex(random_bytes(3)) . '.test', 'status' => Hosting::STATUS_ACTIVE, 'current_expiry_date' => '2027-01-01', 'created_at' => time(), 'updated_at' => time()], false);
            $this->assertTrue($hosting->save(false));
            $stored = (string) Hosting::find()->select('cpanel_password')->where(['id' => $hosting->id])->scalar();
            $this->assertStringStartsWith('enc:', $stored);
            $this->assertStringNotContainsString('S3cret', $stored);
            $this->assertSame('S3cret!pass', Hosting::findOne($hosting->id)->getCpanelPasswordPlain());

            // Saving the form with the password field left blank keeps it.
            $again = Hosting::findOne($hosting->id);
            $again->setAttributes(['new_cpanel_password' => '']);
            $this->assertTrue($again->save(false));
            $this->assertSame('S3cret!pass', Hosting::findOne($hosting->id)->getCpanelPasswordPlain());

            // Old plain-text values still read back.
            $legacy = new Hosting(['cpanel_password' => 'old-plain']);
            $this->assertSame('old-plain', $legacy->getCpanelPasswordPlain());
        } finally {
            $tx->rollBack();
        }
    }
}
