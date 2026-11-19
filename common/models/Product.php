<?php

declare(strict_types=1);

namespace common\models;

use common\models\traits\AutoTimestampTrait;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;

/**
 * A website theme or plugin sold from the store at /store: shown with screenshots and a live demo, priced for a
 * single-site licence and, optionally, an unlimited-sites licence, in rupees (India) and US dollars (abroad).
 *
 * @property-read ProductImage[] $images screenshots, in order
 */
class Product extends base\Product
{
    use AutoTimestampTrait;

    public const STATUS_DRAFT = 0;
    public const STATUS_PUBLISHED = 1;

    /** Where screenshots and covers live, under the frontend web root. */
    public const UPLOAD_DIR = 'uploads/store';

    /** Screenshots a product can have. */
    public const MAX_IMAGES = 12;

    public const TECHNOLOGIES = ['html' => 'HTML', 'wordpress' => 'WordPress', 'yii2' => 'Yii2'];
    public const KINDS = ['theme' => 'Theme', 'plugin' => 'Plugin'];
    public const LICENCES = ['single' => 'Single site', 'unlimited' => 'Unlimited sites'];

    /** Months of support (setup help and questions) from payment; updates are free for life. See site/licence. */
    public const SUPPORT_MONTHS = 6;

    /** Files buyers download, outside the web root (under @storage). */
    public const FILES_DIR = 'store-files';

    #[\Override]
    public function behaviors(): array
    {
        return ArrayHelper::merge(parent::behaviors(), [TimestampBehavior::class]);
    }

    /** Set by the app only, never from a form ("!"). */
    #[\Override]
    public function scenarios(): array
    {
        $internal = ['cover_image', 'download_file', 'download_name', 'created_at', 'updated_at'];

        return array_map(
            static fn(array $attributes): array => array_map(static fn(string $name): string => in_array($name, $internal, true) ? '!' . $name : $name, $attributes),
            parent::scenarios(),
        );
    }

    #[\Override]
    public function rules(): array
    {
        return array_merge([
            [['name', 'slug', 'summary', 'description', 'features', 'changelog', 'demo_url', 'version', 'seo_title', 'seo_description'], 'trim'],
            ['slug', 'filter', 'filter' => fn($slug): string => Inflector::slug((string) ($slug ?: $this->name))],
            ['slug', 'required', 'message' => 'Use at least one letter or digit in the name.', 'enableClientValidation' => false],
            ['status', 'default', 'value' => self::STATUS_DRAFT],
            ['status', 'in', 'range' => [self::STATUS_DRAFT, self::STATUS_PUBLISHED]],
            ['technology', 'in', 'range' => array_keys(self::TECHNOLOGIES)],
            ['kind', 'in', 'range' => array_keys(self::KINDS)],
            // Only http(s): shown as a link on the website.
            ['demo_url', 'url', 'defaultScheme' => 'https'],
            [['price_single_inr', 'price_single_usd', 'price_unlimited_inr', 'price_unlimited_usd'], 'number', 'min' => 1, 'max' => 9999999],
            // Shown without paise, so search engines see the same price as visitors.
            [['price_single_inr', 'price_unlimited_inr'], 'integer', 'message' => 'Whole rupees, without paise.'],
            // An unlimited-sites licence is offered with both prices, or not at all.
            [['price_unlimited_inr', 'price_unlimited_usd'], 'required', 'when' => fn(): bool => (string) $this->price_unlimited_inr !== '' || (string) $this->price_unlimited_usd !== '', 'enableClientValidation' => false, 'message' => 'Give both unlimited-sites prices, or leave both empty.'],
            ['price_unlimited_inr', 'compare', 'compareAttribute' => 'price_single_inr', 'operator' => '>', 'type' => 'number', 'skipOnEmpty' => true, 'message' => 'The unlimited-sites price should be higher than the single-site price.'],
            ['price_unlimited_usd', 'compare', 'compareAttribute' => 'price_single_usd', 'operator' => '>', 'type' => 'number', 'skipOnEmpty' => true, 'message' => 'The unlimited-sites price should be higher than the single-site price.'],
            ['sort_order', 'integer', 'min' => 0, 'max' => 9999],
        ], parent::rules());
    }

    #[\Override]
    public function attributeLabels(): array
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'slug' => 'Web address',
            'kind' => 'Type',
            'summary' => 'Short summary',
            'features' => 'Features (one a line)',
            'changelog' => "What's new",
            'demo_url' => 'Live demo address',
            'price_single_inr' => 'Single site, ₹',
            'price_single_usd' => 'Single site, US$',
            'price_unlimited_inr' => 'Unlimited sites, ₹',
            'price_unlimited_usd' => 'Unlimited sites, US$',
            'sort_order' => 'Order in the store',
            'seo_title' => 'Search title',
            'seo_description' => 'Search description',
        ]);
    }

    /** Published, for the public store. */
    public static function findPublic(): ActiveQuery
    {
        return self::find()->where(['status' => self::STATUS_PUBLISHED]);
    }

    public function isPublic(): bool
    {
        return (int) $this->status === self::STATUS_PUBLISHED;
    }

    public function getImages(): ActiveQuery
    {
        return $this->hasMany(ProductImage::class, ['product_id' => 'id'])->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC]);
    }

    /** @return string[] */
    public function featureList(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $this->features) ?: [])));
    }

    /** @return string[] paragraphs of the description */
    public function paragraphs(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R\s*\R/', (string) $this->description) ?: [])));
    }

    public function hasUnlimited(): bool
    {
        return $this->price_unlimited_inr !== null && $this->price_unlimited_usd !== null;
    }

    /** The price of a licence, in US dollars for buyers abroad, else in rupees; null when that licence isn't offered. */
    public function price(string $licence, bool $usd): ?float
    {
        $price = match ($licence) {
            'single' => $usd ? $this->price_single_usd : $this->price_single_inr,
            'unlimited' => $usd ? $this->price_unlimited_usd : $this->price_unlimited_inr,
            default => null,
        };

        return $price === null ? null : (float) $price;
    }

    public function downloadPath(): ?string
    {
        return $this->download_file ? \Yii::getAlias('@storage/' . self::FILES_DIR . '/' . $this->download_file) : null;
    }

    /** e.g. "WordPress theme" */
    public function typeLabel(): string
    {
        return (self::TECHNOLOGIES[$this->technology] ?? $this->technology) . ' ' . strtolower(self::KINDS[$this->kind] ?? $this->kind);
    }

    #[\Override]
    public function afterSave($insert, $changedAttributes): void
    {
        parent::afterSave($insert, $changedAttributes);
        // Bing hears at once about a product that is in the store, or just left it.
        $wasPublic = !$insert && (int) (array_key_exists('status', $changedAttributes) ? $changedAttributes['status'] : $this->status) === self::STATUS_PUBLISHED;
        if ($this->isPublic() || $wasPublic) {
            \common\services\IndexNowService::queue('/store/' . $this->slug);
            \common\services\IndexNowService::queue('/store'); // the list changes too
        }
        if ($wasPublic && isset($changedAttributes['slug']) && $changedAttributes['slug'] !== $this->slug) {
            \common\services\IndexNowService::queue('/store/' . $changedAttributes['slug']);
        }
    }

    #[\Override]
    public function afterDelete(): void
    {
        parent::afterDelete();
        if ($this->isPublic()) {
            \common\services\IndexNowService::queue('/store/' . $this->slug);
        }
    }
}
