<?php

declare(strict_types=1);

namespace backend\tests\Unit;

use Codeception\Test\Unit;
use common\components\CustomYii;
use console\controllers\InstallController;
use Yii;
use yii\db\Query;
use yii\db\Transaction;

/** Setting up a copy sold to another business: its details in place of ours, and its own logo or none. */
final class InstallTest extends Unit
{
    private ?Transaction $_dbTransaction = null;
    /** @var array<string, mixed> */
    private array $_env = [];
    private mixed $_portalOnly = false;

    protected function _before(): void
    {
        parent::_before();
        $this->_dbTransaction = Yii::$app->db->beginTransaction();
        $this->_env = $_ENV;
        $this->_portalOnly = Yii::$app->params['portalOnly'] ?? false;
    }

    protected function _after(): void
    {
        $this->_dbTransaction?->rollBack();
        $_ENV = $this->_env;
        Yii::$app->params['portalOnly'] = $this->_portalOnly;
        Yii::$app->cache->flush();
        // The settings row CustomYii keeps in memory, read during the test.
        (new \ReflectionProperty(CustomYii::class, '_settings'))->setValue(null, null);
        parent::_after();
    }

    public function testBrandPutsTheBusinessInPlaceOfUsOnce(): void
    {
        Yii::$app->db->createCommand()->update('{{%site_setting}}', ['site_name' => 'Miracle Web Technologies', 'mobile_1' => 919654511842, 'address_line_1' => 'A-26B'])->execute();
        $_ENV['SENDER_NAME'] = 'Acme Hosting';
        $_ENV['FRONTEND_BASE_URL'] = 'https://billing.acme.test/';
        $_ENV['ADMIN_EMAIL'] = 'owner@acme.test';
        $_ENV['SUPPORT_EMAIL'] = 'help@acme.test';
        $_ENV['INSTALL_WEBSITE'] = 'https://acme.test';
        Yii::$app->db->createCommand()->update('{{%admin}}', ['email' => 'miraclewebtechnologies@gmail.com'], ['username' => 'superadmin'])->execute();
        // Its messages are kept, not printed in the test run.
        $install = new class ('install', Yii::$app) extends InstallController {
            public string $said = '';

            /** @return int */
            public function stdout($string)
            {
                $this->said .= $string;

                return strlen($string);
            }

            /** @return int */
            public function stderr($string)
            {
                return $this->stdout($string);
            }
        };
        Yii::$app->params['portalOnly'] = false;
        $this->assertNotSame(0, $install->actionBrand(), 'never on our own site');
        $this->assertSame('Miracle Web Technologies', (new Query())->select('site_name')->from('{{%site_setting}}')->scalar());
        Yii::$app->params['portalOnly'] = true;
        $this->assertSame(0, $install->actionBrand());
        $settings = (new Query())->from('{{%site_setting}}')->one();
        $this->assertSame('Acme Hosting', $settings['site_name']);
        $this->assertSame('https://acme.test', $settings['site_url'], 'their own website');
        $this->assertSame('help@acme.test', $settings['email_1'], 'customers write to support, not the owner');
        $this->assertSame('help@acme.test', $settings['grievance_email']);
        $this->assertNull($settings['address_line_1'], 'our address is gone');
        $this->assertSame(0, (int) $settings['mobile_1'], 'our phone is gone');
        (new \ReflectionProperty(\common\components\CustomYii::class, '_settings'))->setValue(null, null);
        $this->assertSame(['help@acme.test'], \common\models\Invoice::supplierLines(), 'invoices show no phone for the 0, not +0');
        $this->assertSame('owner@acme.test', (new Query())->select('email')->from('{{%admin}}')->where(['username' => 'superadmin'])->scalar());

        // Run again (install.php can be): what the business has set since stays.
        Yii::$app->db->createCommand()->update('{{%site_setting}}', ['address_line_1' => '5 Their Road'])->execute();
        $this->assertSame(0, $install->actionBrand());
        $this->assertSame('5 Their Road', (new Query())->select('address_line_1')->from('{{%site_setting}}')->scalar());
    }

    public function testACopyShowsItsOwnLogoOrNone(): void
    {
        Yii::$app->params['portalOnly'] = false; // our site first, whatever the local .env says
        $this->assertSame('/images/logo.svg', CustomYii::logoUrl('/images/logo.svg'), 'ours on our site');
        $this->assertSame('Website development, hosting & maintenance', CustomYii::invoiceServicesLine(), 'our invoices unchanged');
        // A frontend folder of its own: the real frontend/web/brand is never touched or read.
        $frontend = Yii::getAlias('@frontend');
        $dir = sys_get_temp_dir() . '/brand-test-' . bin2hex(random_bytes(4));
        mkdir($dir . '/web/brand', 0775, true);
        Yii::setAlias('@frontend', $dir);
        Yii::$app->params['portalOnly'] = true;
        $base = Yii::$app->params['frontendBaseUrl'] ?? '';
        Yii::$app->params['frontendBaseUrl'] = 'https://billing.acme.test'; // whatever the local .env says
        try {
            $this->assertNull(CustomYii::logoUrl('/images/logo.svg'), 'never ours on a copy');
            $this->assertNull(CustomYii::logoFile('/x/logo.png'));
            $this->assertNull(CustomYii::faviconUrl('/favicon.ico'), 'no icon of ours');
            $this->assertSame('<span>' . htmlspecialchars(CustomYii::businessName()) . '</span>', CustomYii::logoImg('/images/logo.svg'), 'the name instead');

            file_put_contents($dir . '/web/brand/logo.png', 'png');
            $this->assertStringContainsString('/brand/logo.png?v=', (string) CustomYii::logoUrl('/images/logo.svg'));
            $this->assertNull(CustomYii::logoUrl('/images/logo.svg', true), 'no light version: the name on dark, not a logo nobody sees');
            $this->assertSame($dir . '/web/brand/logo.png', CustomYii::logoFile('/x/logo.png'));
            file_put_contents($dir . '/web/brand/logo-white.png', 'png');
            $this->assertStringContainsString('/brand/logo-white.png?v=', (string) CustomYii::logoUrl('/images/logo.svg', true), 'the light one on dark');
        } finally {
            Yii::setAlias('@frontend', $frontend);
            Yii::$app->params['frontendBaseUrl'] = $base;
            array_map('unlink', glob($dir . '/web/brand/*') ?: []);
            rmdir($dir . '/web/brand');
            rmdir($dir . '/web');
            rmdir($dir);
        }
    }
}
