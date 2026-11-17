<?php

declare(strict_types=1);

namespace common\models;

use yii\db\ActiveQuery;

/**
 * A post's title, summary and text as they were before an edit, so it can be compared and restored.
 *
 * @property Admin|null $author
 */
class BlogPostRevision extends base\BlogPostRevision
{
    /** Older versions beyond this many per post are removed. */
    public const KEEP = 30;

    #[\Override]
    public function getAuthor(): ActiveQuery
    {
        return $this->hasOne(Admin::class, ['id' => 'author_id']);
    }
}
