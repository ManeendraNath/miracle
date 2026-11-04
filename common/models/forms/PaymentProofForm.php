<?php

declare(strict_types=1);

namespace common\models\forms;

use yii\base\Model;

final class PaymentProofForm extends Model
{
    use \common\models\traits\ScalarInputTrait;

    public string $gateway_payment_id = '';
    public string $user_payment_notes = '';
    public $receiptFile;

    #[\Override]
    public function rules(): array
    {
        return [
            [['gateway_payment_id', 'user_payment_notes'], 'trim'],
            ['gateway_payment_id', 'required'],
            ['gateway_payment_id', 'string', 'min' => 6, 'max' => 100],
            // A Razorpay payment id would take the reference the online payment itself is recorded under.
            ['gateway_payment_id', 'match', 'not' => true, 'pattern' => '/^pay_/i', 'message' => 'That is an online (Razorpay) payment reference. Online payments are confirmed automatically, so no proof is needed.'],
            ['user_payment_notes', 'string', 'max' => 1000],
            ['receiptFile', 'required'],
            ['receiptFile', 'file', 'extensions' => ['jpg', 'jpeg', 'png', 'pdf'], 'mimeTypes' => ['image/jpeg', 'image/png', 'application/pdf'], 'maxSize' => 5 * 1024 * 1024, 'checkExtensionByMimeType' => true],
        ];
    }

    #[\Override]
    public function attributeLabels(): array
    {
        return [
            'gateway_payment_id' => 'UPI / UTR / Transaction Reference',
            'user_payment_notes' => 'Payment Notes',
            'receiptFile' => 'Payment Receipt',
        ];
    }
}
