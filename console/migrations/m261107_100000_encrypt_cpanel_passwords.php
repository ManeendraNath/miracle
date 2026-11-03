<?php

declare(strict_types=1);

use common\helpers\SecretBox;
use yii\db\Migration;
use yii\db\Query;

/**
 * cPanel passwords were stored in plain text (and so copied into every backup). Encrypts the
 * existing ones with TWO_FACTOR_KEY; Hosting encrypts new ones on save. Needs the key only if
 * there are passwords to encrypt.
 */
final class m261107_100000_encrypt_cpanel_passwords extends Migration
{
    public function safeUp(): void
    {
        // Encrypted values are much longer than the password (about 3x plus 130 characters).
        $this->alterColumn('{{%hosting}}', 'cpanel_password', $this->text()->null());
        $rows = (new Query())->select(['id', 'cpanel_password'])->from('{{%hosting}}')
            ->where(['not', ['cpanel_password' => null]])->andWhere(['<>', 'cpanel_password', ''])->all($this->db);
        foreach ($rows as $row) {
            if (!SecretBox::isEncrypted($row['cpanel_password'])) {
                $this->update('{{%hosting}}', ['cpanel_password' => SecretBox::encrypt((string) $row['cpanel_password'], 'cpanel-password')], ['id' => $row['id']]);
            }
        }
    }

    public function safeDown(): bool
    {
        echo "Encrypted cPanel passwords are left encrypted (Hosting still reads them).\n";

        return true;
    }
}
