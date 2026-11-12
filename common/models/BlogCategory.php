<?php

declare(strict_types=1);

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\helpers\Inflector;

/**
 * A blog category, shown at /blog/category/{slug}.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int $created_at
 * @property int $updated_at
 */
class BlogCategory extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%blog_category}}';
    }

    public function behaviors(): array
    {
        return [TimestampBehavior::class];
    }

    public function rules(): array
    {
        return [
            [['name', 'slug'], 'trim'],
            ['name', 'required'],
            ['name', 'string', 'max' => 100],
            ['slug', 'filter', 'filter' => fn ($slug): string => Inflector::slug((string) ($slug ?: $this->name))],
            ['slug', 'required', 'message' => 'Use at least one letter or digit in the name.', 'enableClientValidation' => false],
            ['slug', 'string', 'max' => 120],
            ['slug', 'unique'],
        ];
    }

    public function attributeLabels(): array
    {
        return ['slug' => 'Web address'];
    }

    /** @return array<int, string> id => name, for dropdowns */
    public static function options(): array
    {
        return self::find()->select(['name', 'id'])->orderBy(['name' => SORT_ASC])->indexBy('id')->column();
    }
}
