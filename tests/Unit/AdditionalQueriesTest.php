<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use JundFauz\EloquentKompact\Model\Traits\AdditionalQueries;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AdditionalQueriesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->morphs('commentable');
        });

        DB::table('comments')->insert([
            ['commentable_type' => MorphPost::class, 'commentable_id' => 1],
            ['commentable_type' => MorphPost::class, 'commentable_id' => 2],
            ['commentable_type' => 'video', 'commentable_id' => 1],
        ]);
    }

    protected function tearDown(): void
    {
        Relation::morphMap([], false);

        parent::tearDown();
    }

    #[Test]
    public function it_filters_by_the_morph_class(): void
    {
        $this->assertSame([1, 2], MorphComment::whereMorphType('commentable', MorphPost::class)->pluck('id')->all());
    }

    #[Test]
    public function it_filters_by_the_morph_alias(): void
    {
        Relation::morphMap(['video' => MorphVideo::class]);

        $this->assertSame([3], MorphComment::whereMorphType('commentable', MorphVideo::class)->pluck('id')->all());
    }

    #[Test]
    public function it_uses_the_type_column(): void
    {
        $this->assertSame(
            'select * from "comments" where "commentable_type" = ?',
            MorphComment::whereMorphType('commentable', MorphPost::class)->toSql()
        );
    }
}

class MorphComment extends Model
{
    use AdditionalQueries;

    protected $table = 'comments';
}

class MorphPost extends Model {}

class MorphVideo extends Model {}
