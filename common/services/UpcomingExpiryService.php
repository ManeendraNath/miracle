<?php

declare(strict_types=1);

namespace common\services;

use common\models\Domains;
use common\models\Hosting;
use common\models\Maintenance;
use DateTimeImmutable;
use yii\db\ActiveRecord;

final class UpcomingExpiryService
{

    public const TYPE_DOMAIN = 'domain';
    public const TYPE_HOSTING = 'hosting';
    public const TYPE_MAINTENANCE = 'maintenance';
    public const WINDOW_ALL = 'all';
    public const WINDOW_EXPIRED = 'expired';
    public const WINDOW_7 = '7';
    public const WINDOW_30 = '30';
    public const WINDOW_60 = '60';
    public const WINDOW_90 = '90';

    public static function getTypes(): array
    {
        return [
            self::TYPE_DOMAIN => 'Domain',
            self::TYPE_HOSTING => 'Hosting',
            self::TYPE_MAINTENANCE => 'Maintenance',
        ];
    }

    public static function getWindows(): array
    {
        return [
            self::WINDOW_ALL => 'All Expiries',
            self::WINDOW_EXPIRED => 'Expired',
            self::WINDOW_7 => 'Next 7 Days',
            self::WINDOW_30 => 'Next 30 Days',
            self::WINDOW_60 => 'Next 60 Days',
            self::WINDOW_90 => 'Next 90 Days',
        ];
    }

    public function getItems(?string $type = null, ?int $userId = null, string $window = self::WINDOW_60): array
    {
        $items = [];
        foreach ($this->getSources() as $sourceType => $source) {
            if ($type !== null && $type !== $sourceType) {
                continue;
            }
            $items = array_merge($items, $this->loadSource($sourceType, $source, $userId));
        }

        return array_values(
                array_filter(
                        $items,
                        fn(array $item): bool => $this->matchesWindow($item['days_remaining'], $window)
                )
        );
    }

    public function getSummary(?string $type = null, ?int $userId = null): array
    {
        $items = $this->getItems($type, $userId, self::WINDOW_ALL);
        $summary = [
            'expired' => 0,
            'next7' => 0,
            'next30' => 0,
            'next60' => 0,
        ];

        foreach ($items as $item) {
            $remaining = $item['days_remaining'];

            if ($remaining < 0) {
                $summary['expired']++;
                continue;
            }

            if ($remaining <= 7) {
                $summary['next7']++;
            }

            if ($remaining <= 30) {
                $summary['next30']++;
            }

            if ($remaining <= 60) {
                $summary['next60']++;
            }
        }

        return $summary;
    }

    private function getSources(): array
    {
        return [
            self::TYPE_DOMAIN => [
                'class' => Domains::class,
                'nameAttribute' => 'domain_url',
            ],
            self::TYPE_HOSTING => [
                'class' => Hosting::class,
                'nameAttribute' => 'primary_domain',
            ],
            self::TYPE_MAINTENANCE => [
                'class' => Maintenance::class,
                'nameAttribute' => 'website_url',
            ],
        ];
    }

    private function loadSource(string $type, array $source, ?int $userId): array
    {
        $class = $source['class'];
        $nameAttribute = $source['nameAttribute'];

        $query = $class::find()
                ->with('user')
                ->andWhere(['not', ['current_expiry_date' => null]])
                ->andWhere(['<>', 'current_expiry_date', '']);
        if ($userId !== null) {
            $query->andWhere(['user_id' => $userId]);
        }
        $items = [];

        foreach ($query->all() as $model) {
            if (!$model instanceof ActiveRecord) {
                continue;
            }
            $item = $this->normaliseModel($model, $type, $nameAttribute);
            if ($item !== null) {
                $items[] = $item;
            }
        }
        return $items;
    }

    private function normaliseModel(ActiveRecord $model, string $type, string $nameAttribute): ?array
    {
        $expiryDate = trim((string) $model->getAttribute('current_expiry_date'));
        $expiry = DateTimeImmutable::createFromFormat('!Y-m-d', $expiryDate);
        if ($expiry === false || $expiry->format('Y-m-d') !== $expiryDate) {
            return null;
        }

        $today = new DateTimeImmutable('today');
        $daysRemaining = (int) $today->diff($expiry)->format('%r%a');
        $user = $model->user;
        return [
            'id' => (int) $model->getPrimaryKey(),
            'user_id' => (int) $model->getAttribute('user_id'),
            'type' => $type,
            'name' => (string) $model->getAttribute($nameAttribute),
            'expiry_date' => $expiryDate,
            'days_remaining' => $daysRemaining,
            'username' => $user !== null ? (string) $user->username : '',
            'email' => $user !== null ? (string) $user->email : '',
        ];
    }

    private function matchesWindow(int $daysRemaining, string $window): bool
    {
        if ($window === self::WINDOW_ALL) {
            return true;
        }

        if ($window === self::WINDOW_EXPIRED) {
            return $daysRemaining < 0;
        }

        $days = match ($window) {
            self::WINDOW_7 => 7,
            self::WINDOW_30 => 30,
            self::WINDOW_60 => 60,
            self::WINDOW_90 => 90,
            default => 60,
        };

        return $daysRemaining >= 0 && $daysRemaining <= $days;
    }
}
