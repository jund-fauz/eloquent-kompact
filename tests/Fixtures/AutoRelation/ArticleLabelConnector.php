<?php

namespace JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation;

use JundFauz\EloquentKompact\Model\KompactPivot;

class ArticleLabelConnector extends KompactPivot
{
    protected $table = 'ar_article_label_connector';

    public $timestamps = false;

    protected static array $pivotBetween = [Article::class, Label::class];
}
