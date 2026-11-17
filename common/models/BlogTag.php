<?php

declare(strict_types=1);

namespace common\models;

use common\models\traits\AutoTimestampTrait;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;

/** A blog tag, shown at /blog/tag/{slug}. Made from the comma-separated tags typed on a post. */
class BlogTag extends base\BlogTag
{
    use AutoTimestampTrait;

    #[\Override]
    public function behaviors(): array
    {
        return ArrayHelper::merge(parent::behaviors(), [TimestampBehavior::class]);
    }

    public function getPosts(): ActiveQuery
    {
        return $this->hasMany(BlogPost::class, ['id' => 'post_id'])->viaTable('{{%blog_post_tag}}', ['tag_id' => 'id']);
    }

    /** The web address part for a tag name; "C++" and "C#" stay apart from "C". Empty when nothing usable is left. */
    public static function slugFor(string $name): string
    {
        return mb_substr(Inflector::slug(strtr($name, ['+' => ' plus ', '#' => ' sharp '])), 0, 80);
    }

    /**
     * The tags named in "SEO, Web apps, seo" (case-insensitive, first spelling wins), created when new.
     *
     * @return self[]
     */
    public static function findOrCreate(string $names): array
    {
        $tags = [];
        foreach (array_filter(array_map('trim', explode(',', $names))) as $name) {
            $name = mb_substr($name, 0, 60);
            $slug = self::slugFor($name);
            if ($slug === '' || isset($tags[$slug])) {
                continue;
            }
            $tag = self::findOne(['slug' => $slug]) ?? new self(['name' => $name, 'slug' => $slug]);
            if ($tag->getIsNewRecord() && !$tag->save()) {
                throw new \RuntimeException('Tag "' . $name . '" could not be saved: ' . json_encode($tag->errors));
            }
            $tags[$slug] = $tag;
        }

        return array_values($tags);
    }
}
