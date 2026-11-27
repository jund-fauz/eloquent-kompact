<?php

namespace JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LegacyReviewer extends Model
{
    use SoftDeletes;

    protected $table = 'ar_legacy_reviewers';

    public $timestamps = false;

    protected static bool $includeSoftDeletedData = true;

    public static function tableName(): string
    {
        return 'ar_legacy_reviewers';
    }
}
