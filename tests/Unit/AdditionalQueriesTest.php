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
    public function it_filters_by_the_columns_of_another_model(): void
    {
        $comment = MorphComment::find(1);

        $this->assertSame([1, 2], MorphComment::whereSameWith($comment, 'commentable_type')->pluck('id')->all());
        $this->assertSame([1], MorphComment::whereSameWith($comment, 'commentable_type', 'commentable_id')->pluck('id')->all());
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

    #[Test]
    public function it_excludes_the_morph_class(): void
    {
        $this->assertSame([3], MorphComment::whereMorphTypeNot('commentable', MorphPost::class)->pluck('id')->all());
    }

    #[Test]
    public function it_excludes_the_morph_alias(): void
    {
        Relation::morphMap(['video' => MorphVideo::class]);

        $this->assertSame([1, 2], MorphComment::whereMorphTypeNot('commentable', MorphVideo::class)->pluck('id')->all());
    }

    #[Test]
    public function it_negates_the_type_column(): void
    {
        $this->assertSame(
            'select * from "comments" where not "commentable_type" = ?',
            MorphComment::whereMorphTypeNot('commentable', MorphPost::class)->toSql()
        );
    }

    #[Test]
    public function it_checks_the_morph_class(): void
    {
        $comment = MorphComment::find(1);

        $this->assertTrue($comment->isMorphModelSame('commentable', MorphPost::class));
        $this->assertFalse($comment->isMorphModelSame('commentable', MorphVideo::class));
    }

    #[Test]
    public function it_checks_the_morph_alias(): void
    {
        Relation::morphMap(['video' => MorphVideo::class]);

        $this->assertTrue(MorphComment::find(3)->isMorphModelSame('commentable', MorphVideo::class));
        $this->assertFalse(MorphComment::find(1)->isMorphModelSame('commentable', MorphVideo::class));
    }
}

class MorphComment extends Model
{
    use AdditionalQueries;

    protected $table = 'comments';
}

class MorphPost extends Model {}

class MorphVideo extends Model {}
