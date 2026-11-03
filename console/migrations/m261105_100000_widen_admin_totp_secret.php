<?php

declare(strict_types=1);

use common\helpers\SecretBox;
use yii\db\Migration;
use yii\db\Query;

/**
 * admin.totp_secret now holds the encrypted secret (about 160 characters).
 */
final class m261105_100000_widen_admin_totp_secret extends Migration
{
    public function safeUp(): void
    {
        $this->alterColumn('{{%admin}}', 'totp_secret', $this->string(255)->null());
    }

    public function safeDown(): void
    {
        // Encrypted secrets don't fit in 64 characters: store them plain again first
        // (Admin re-encrypts a plain secret on use), or abort rather than truncate them.
        $rows = (new Query())->select(['id', 'totp_secret'])->from('{{%admin}}')->where(['not', ['totp_secret' => null]])->all($this->db);
        foreach ($rows as $row) {
            $plain = SecretBox::decrypt((string) $row['totp_secret']);
            if ($plain === null || strlen($plain) > 64) {
                throw new \RuntimeException("Two-factor secret of admin #{$row['id']} can't be decrypted (TWO_FACTOR_KEY); not rolling back.");
            }
            $this->update('{{%admin}}', ['totp_secret' => $plain], ['id' => $row['id']]);
        }
        $this->alterColumn('{{%admin}}', 'totp_secret', $this->string(64)->null());
    }
}
