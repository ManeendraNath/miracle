<?php

declare(strict_types=1);

namespace common\models;

/** An old web address of a post whose address changed: /blog/{old_slug} sends visitors to the post (301). */
class BlogPostRedirect extends base\BlogPostRedirect
{
}
