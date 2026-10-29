<?php

declare(strict_types=1);

namespace common\services\notification;

use common\models\WhatsAppLog;
use Yii;
use yii\httpclient\Client;

final class WhatsAppService
{

    private const STATUS_PENDING = 'pending';
    private const STATUS_DUPLICATE = 'duplicate';
    private const STATUS_SUCCESS = 'success';
    private const STATUS_FAILED = 'failed';

    public static function sendTemplate(string $mobile, string $templateName, array $components = [], ?string $eventKey = null): bool
    {
        if ($eventKey !== null && self::wasSentRecently($eventKey)) {
            self::log(
                    mobile: $mobile,
                    template: $templateName,
                    payload: [],
                    response: null,
                    status: self::STATUS_DUPLICATE,
                    error: 'Duplicate send prevented',
                    eventKey: $eventKey
            );
            Yii::warning(['duplicate' => true, 'eventKey' => $eventKey], __METHOD__);
            return true;
        }
        if (!Yii::$app->params['whatsapp']['enabled']) {
            Yii::info(
                    [
                        'provider' => Yii::$app->params['whatsapp']['provider'],
                        'reason' => 'disabled',
                        'mobile' => $mobile,
                        'template' => $templateName,
                    ],
                    __METHOD__
            );
            return true;
        }
        $payload = [
            'integrated_number' =>
            Yii::$app->params['whatsapp']['integratedNumber'],
            'content_type' => 'template',
            'payload' => [
                'messaging_product' => 'whatsapp',
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => [
                        'code' => 'en',
                        'policy' => 'deterministic',
                    ],
                    'to_and_components' => [
                        [
                            'to' => [$mobile],
                            'components' => $components,
                        ],
                    ],
                ],
            ],
        ];

        try {

            $client = new Client(['transport' => 'yii\httpclient\CurlTransport']);
            $attempts = 3;
            for ($attempt = 1; $attempt <= $attempts; $attempt++) {
                try {
                    $response = $client
                            ->createRequest()
                            ->setMethod('POST')
                            ->setUrl('https://control.msg91.com/api/v5/whatsapp/whatsapp-outbound-message/bulk/')
                            ->addHeaders([
                                'authkey' => Yii::$app->params['whatsapp']['apiKey'],
                                'Content-Type' => 'application/json',
                            ])
                            ->setOptions([
                                CURLOPT_TIMEOUT => 15,
                            ])
                            ->setContent(json_encode($payload, JSON_THROW_ON_ERROR))
                            ->send();

                    $responseContent = substr((string) $response->content, 0, 5000);
                    if ($response->isOk) {
                        self::log(
                                mobile: $mobile,
                                template: $templateName,
                                payload: $payload,
                                response: $responseContent,
                                status: self::STATUS_SUCCESS,
                                error: null,
                                eventKey: $eventKey
                        );
                        return true;
                    }

                    Yii::warning(['attempt' => $attempt, 'response' => $responseContent], __METHOD__);

                    if ($attempt < $attempts) {
                        sleep($attempt);
                        continue;
                    }

                    self::log(
                            mobile: $mobile,
                            template: $templateName,
                            payload: $payload,
                            response: $responseContent,
                            status: self::STATUS_FAILED,
                            error: 'MSG91 returned non-success response',
                            eventKey: $eventKey
                    );

                    return false;
                } catch (\Throwable $exception) {

                    if ($attempt < $attempts) {

                        Yii::warning(
                                [
                                    'attempt' => $attempt,
                                    'error' => $exception->getMessage(),
                                ],
                                __METHOD__
                        );

                        sleep($attempt);

                        continue;
                    }

                    throw $exception;
                }
            }

            return false;
        } catch (\Throwable $exception) {

            self::log(
                    mobile: $mobile,
                    template: $templateName,
                    payload: $payload,
                    response: null,
                    status: self::STATUS_FAILED,
                    error: $exception->getMessage(),
                    eventKey: $eventKey
            );

            Yii::error(
                    [
                        'provider' =>
                        Yii::$app->params['whatsapp']['provider'],
                        'mobile' => $mobile,
                        'template' => $templateName,
                        'error' =>
                        $exception->getMessage(),
                    ],
                    __METHOD__
            );

            return false;
        }
    }

    private static function log(string $mobile, string $template, mixed $payload, mixed $response, string $status, ?string $error, ?string $eventKey): void
    {

        try {
            $log = new WhatsAppLog();
            $log->mobile = $mobile;
            $log->template_name = $template;
            $log->event_key = $eventKey;
            $log->request_payload = json_encode(self::sanitizePayload(is_array($payload) ? $payload : []), JSON_UNESCAPED_UNICODE);
            $log->response_payload = $response !== null ? substr((string) $response, 0, 5000) : null;
            $log->status = $status;
            $log->error_message = $error;
            $log->created_at = time();
            $log->save(false);
        } catch (\Throwable) {
            // Never break business workflow
        }
    }

    private static function sanitizePayload(array $payload): array
    {
        return [
            'template' => $payload['payload']['template']['name'] ?? null,
            'integrated_number' => $payload['integrated_number'] ?? null,
        ];
    }

    private static function wasSentRecently(string $eventKey): bool
    {
        return WhatsAppLog::find()
                        ->where(['event_key' => $eventKey, 'status' => self::STATUS_SUCCESS])
                        ->andWhere(['>', 'created_at', time() - 3600])
                        ->exists();
    }
}
