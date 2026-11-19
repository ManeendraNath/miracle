<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "product".
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $technology
 * @property string $kind
 * @property string $summary
 * @property string $description
 * @property string|null $features
 * @property string|null $cover_image
 * @property string|null $demo_url
 * @property string|null $version
 * @property float $price_single_inr
 * @property float $price_single_usd
 * @property float|null $price_unlimited_inr
 * @property float|null $price_unlimited_usd
 * @property int $status
 * @property int $sort_order
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property int $created_at
 * @property int $updated_at
 *
 * @property ProductImage[] $productImages
 */
class Product extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['features', 'cover_image', 'demo_url', 'version', 'price_unlimited_inr', 'price_unlimited_usd', 'seo_title', 'seo_description'], 'default', 'value' => null],
            [['sort_order'], 'default', 'value' => 0],
            [['name', 'slug', 'technology', 'kind', 'summary', 'description', 'price_single_inr', 'price_single_usd', 'created_at', 'updated_at'], 'required'],
            [['description', 'features'], 'string'],
            [['price_single_inr', 'price_single_usd', 'price_unlimited_inr', 'price_unlimited_usd'], 'number'],
            [['status', 'sort_order', 'created_at', 'updated_at'], 'integer'],
            [['name'], 'string', 'max' => 150],
            [['slug'], 'string', 'max' => 160],
            [['technology', 'version'], 'string', 'max' => 20],
            [['kind'], 'string', 'max' => 10],
            [['summary'], 'string', 'max' => 300],
            [['cover_image', 'demo_url'], 'string', 'max' => 255],
            [['seo_title'], 'string', 'max' => 70],
            [['seo_description'], 'string', 'max' => 170],
            [['slug'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'slug' => 'Slug',
            'technology' => 'Technology',
            'kind' => 'Kind',
            'summary' => 'Summary',
            'description' => 'Description',
            'features' => 'Features',
            'cover_image' => 'Cover Image',
            'demo_url' => 'Demo Url',
            'version' => 'Version',
            'price_single_inr' => 'Price Single Inr',
            'price_single_usd' => 'Price Single Usd',
            'price_unlimited_inr' => 'Price Unlimited Inr',
            'price_unlimited_usd' => 'Price Unlimited Usd',
            'status' => 'Status',
            'sort_order' => 'Sort Order',
            'seo_title' => 'Seo Title',
            'seo_description' => 'Seo Description',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[ProductImages]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProductImages()
    {
        return $this->hasMany(ProductImage::class, ['product_id' => 'id']);
    }
}
