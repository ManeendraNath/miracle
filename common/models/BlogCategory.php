<?php

declare(strict_types=1);

namespace common\models;

use common\models\traits\AutoTimestampTrait;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;
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
class BlogCategory extends base\BlogCategory
{
    use AutoTimestampTrait;

    #[\Override]
    public function behaviors(): array
    {
        return ArrayHelper::merge(parent::behaviors(), [TimestampBehavior::class]);
    }

    #[\Override]
    public function rules(): array
    {
        // Only what the base class lacks, and first: its "slug required" must see the slug made from the name.
        $rules = [
            [['name', 'slug'], 'trim'],
            ['slug', 'filter', 'filter' => fn($slug): string => Inflector::slug((string) ($slug ?: $this->name))],
            ['slug', 'required', 'message' => 'Use at least one letter or digit in the name.', 'enableClientValidation' => false],
        ];
        return ArrayHelper::merge($rules, parent::rules());
    }

    /** Set by the app only, never from a form: still validated, but not mass-assigned ("!"). */
    #[\Override]
    public function scenarios(): array
    {
        $internal = ['created_at', 'updated_at'];

        return array_map(
            static fn(array $attributes): array => array_map(static fn(string $name): string => in_array($name, $internal, true) ? '!' . $name : $name, $attributes),
            parent::scenarios(),
        );
    }

    #[\Override]
    public function attributeLabels(): array
    {
        $attributeLabels = ['slug' => 'Web address'];
        return ArrayHelper::merge(parent::attributeLabels(), $attributeLabels);
    }

    /** @return array<int, string> id => name, for dropdowns */
    public static function options(): array
    {
        return self::find()->select(['name', 'id'])->orderBy(['name' => SORT_ASC])->indexBy('id')->column();
    }
}
