<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "seo_page".
 *
 * @property int $id
 * @property string $route
 * @property string|null $title
 * @property string|null $description
 * @property int $updated_at
 */
class SeoPage extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'seo_page';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'description'], 'default', 'value' => null],
            [['route', 'updated_at'], 'required'],
            [['updated_at'], 'integer'],
            [['route'], 'string', 'max' => 64],
            [['title'], 'string', 'max' => 70],
            [['description'], 'string', 'max' => 170],
            [['route'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'route' => 'Route',
            'title' => 'Title',
            'description' => 'Description',
            'updated_at' => 'Updated At',
        ];
    }
}
