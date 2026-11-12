<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "blog_category".
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int $created_at
 * @property int $updated_at
 *
 * @property BlogPost[] $blogPosts
 */
class BlogCategory extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'blog_category';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'slug', 'created_at', 'updated_at'], 'required'],
            [['created_at', 'updated_at'], 'integer'],
            [['name'], 'string', 'max' => 100],
            [['slug'], 'string', 'max' => 120],
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
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[BlogPosts]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBlogPosts()
    {
        return $this->hasMany(BlogPost::class, ['category_id' => 'id']);
    }
}
