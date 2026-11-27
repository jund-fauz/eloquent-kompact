<?php

namespace JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation;

use JundFauz\EloquentKompact\Model\KompactPivot;

class AuthorPostConnector extends KompactPivot
{
    protected $table = 'ar_author_post_connector';

    public $timestamps = false;

    protected static array $pivotBetween = [
        'writer_id' => Author::class,
        'article_id' => Post::class,
    ];
}
