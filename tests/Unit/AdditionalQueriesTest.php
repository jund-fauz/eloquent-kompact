<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use JundFauz\EloquentKompact\Model\Builder;
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

    #[Test]
    public function it_gets_many_attributes_in_order(): void
    {
        $comment = MorphComment::find(3);

        $this->assertSame(['video', 1], $comment->getMany('commentable_type', 'commentable_id'));
        $this->assertSame([1, 'video'], $comment->getMany('commentable_id', 'commentable_type'));
    }

    #[Test]
    public function it_gets_missing_attributes_as_null(): void
    {
        $this->assertSame([3, null], MorphComment::find(3)->getMany('id', 'missing'));
        $this->assertSame([], MorphComment::find(3)->getMany());
    }

    #[Test]
    public function it_checks_that_an_attribute_is_not_the_value(): void
    {
        $comment = MorphComment::find(3);

        $this->assertFalse($comment->notSameWith('commentable_type', 'video'));
        $this->assertTrue($comment->notSameWith('commentable_type', MorphPost::class));
        $this->assertTrue($comment->notSameWith('commentable_id', '1'));
        $this->assertFalse($comment->notSameWith('missing', null));
    }

    #[Test]
    public function it_assigns_attributes(): void
    {
        $comment = new MorphComment;

        $comment->assign('commentable_type', 'video');
        $comment->assignFrom(MorphComment::find(1), 'commentable_id');

        $this->assertSame(['video', 1], $comment->getMany('commentable_type', 'commentable_id'));
        $this->assertFalse($comment->exists);
    }

    #[Test]
    public function it_assigns_many_attributes(): void
    {
        $comment = (new MorphComment)->forceFill(['commentable_type' => 'video']);

        $comment->assign(['commentable_type' => 'post', 'commentable_id' => 5]);

        $this->assertSame(['post', 5], $comment->getMany('commentable_type', 'commentable_id'));
        $this->assertFalse($comment->exists);
    }

    #[Test]
    public function it_assigns_null_when_no_value_is_given(): void
    {
        $comment = (new MorphComment)->forceFill(['commentable_type' => 'video']);

        $comment->assign('commentable_type');

        $this->assertNull($comment->commentable_type);
    }

    #[Test]
    public function it_assigns_attributes_only_if_blank(): void
    {
        $comment = (new MorphComment)->forceFill(['commentable_type' => 'video', 'commentable_id' => '   ']);

        $comment->assignIfBlank('commentable_type', 'post');
        $comment->assignIfBlank('commentable_id', 5);
        $comment->assignIfBlank('missing', 'x');

        $this->assertSame(['video', 5, 'x'], $comment->getMany('commentable_type', 'commentable_id', 'missing'));
    }

    #[Test]
    public function it_keeps_zero_and_false_because_they_are_not_blank(): void
    {
        $comment = (new MorphComment)->forceFill(['a' => 0, 'b' => '0', 'c' => false]);

        $comment->assignIfBlank(['a' => 5, 'b' => 5, 'c' => 5]);

        $this->assertSame([0, '0', false], $comment->getMany('a', 'b', 'c'));
    }

    #[Test]
    public function it_assigns_many_attributes_only_if_blank(): void
    {
        $comment = (new MorphComment)->forceFill(['commentable_type' => 'video', 'commentable_id' => '']);

        $comment->assignIfBlank(['commentable_type' => 'post', 'commentable_id' => 5, 'missing' => 'x']);

        $this->assertSame(['video', 5, 'x'], $comment->getMany('commentable_type', 'commentable_id', 'missing'));
    }

    #[Test]
    public function it_checks_whether_an_attribute_is_blank(): void
    {
        $comment = (new MorphComment)->forceFill(['commentable_type' => 'video', 'commentable_id' => '   ', 'zero' => 0, 'off' => false]);

        $this->assertFalse($comment->isBlank('commentable_type'));
        $this->assertTrue($comment->isBlank('commentable_id'));
        $this->assertTrue($comment->isBlank('missing'));
        $this->assertFalse($comment->isBlank('zero'));
        $this->assertFalse($comment->isBlank('off'));
    }

    #[Test]
    public function it_assigns_attributes_from_another_model_only_if_blank(): void
    {
        $comment = (new MorphComment)->forceFill(['commentable_type' => '', 'commentable_id' => 7]);

        $comment->assignIfBlankFrom(MorphComment::find(3), 'commentable_type', 'commentable_id');

        $this->assertSame(['video', 7], $comment->getMany('commentable_type', 'commentable_id'));
    }

    #[Test]
    public function it_assigns_attributes_only_if_null(): void
    {
        $comment = (new MorphComment)->forceFill(['commentable_type' => '', 'commentable_id' => 0]);

        $comment->assignIfNull('commentable_type', 'post');
        $comment->assignIfNull('commentable_id', 5);
        $comment->assignIfNull('missing', 'x');

        $this->assertSame(['', 0, 'x'], $comment->getMany('commentable_type', 'commentable_id', 'missing'));
    }

    #[Test]
    public function it_assigns_many_attributes_only_if_null(): void
    {
        $comment = (new MorphComment)->forceFill(['commentable_type' => null, 'commentable_id' => 0]);

        $comment->assignIfNull(['commentable_type' => 'post', 'commentable_id' => 5]);

        $this->assertSame(['post', 0], $comment->getMany('commentable_type', 'commentable_id'));
    }

    #[Test]
    public function it_assigns_attributes_from_another_model_only_if_null(): void
    {
        $comment = (new MorphComment)->forceFill(['commentable_type' => null, 'commentable_id' => 0]);

        $comment->assignIfNullFrom(MorphComment::find(3), 'commentable_type', 'commentable_id');

        $this->assertSame(['video', 0], $comment->getMany('commentable_type', 'commentable_id'));
    }

    #[Test]
    public function it_checks_whether_an_attribute_is_filled(): void
    {
        $comment = (new MorphComment)->forceFill(['commentable_type' => '  ', 'commentable_id' => 0]);

        $this->assertFalse($comment->isFilled('commentable_type'));
        $this->assertTrue($comment->isFilled('commentable_id'));
        $this->assertFalse($comment->isFilled('missing'));
    }

    #[Test]
    public function it_checks_whether_an_attribute_is_null(): void
    {
        $comment = (new MorphComment)->forceFill(['commentable_type' => '', 'commentable_id' => null]);

        $this->assertFalse($comment->isNull('commentable_type'));
        $this->assertTrue($comment->isNotNull('commentable_type'));
        $this->assertTrue($comment->isNull('commentable_id'));
        $this->assertFalse($comment->isNotNull('commentable_id'));
        $this->assertTrue($comment->isNull('missing'));
    }

    #[Test]
    public function it_compares_a_column_with_another_cast(): void
    {
        $model = new CastCompareModel();
        $model->setRawAttributes(['qty' => '5', 'price' => '10.5', 'flag' => 1]);

        $this->assertFalse($model->sameWith('qty', 5));
        $this->assertTrue($model->sameWith('qty', 5, 'integer'));
        $this->assertTrue($model->sameWith('price', '10.50', 'decimal:2'));
        $this->assertTrue($model->sameWith('flag', '1', 'string'));
        $this->assertTrue($model->notSameWith('qty', 5));
        $this->assertFalse($model->notSameWith('qty', 5, 'integer'));
        $this->assertTrue($model->notSameWith('qty', 6, 'integer'));

        // The casts of the model stay as they are
        $this->assertSame('5', $model->qty);
        $this->assertTrue($model->flag);
        $this->assertArrayNotHasKey('qty', $model->getCasts());
    }

    #[Test]
    public function it_compares_a_dirty_column_with_another_cast(): void
    {
        $model = new CastCompareModel();
        $model->setRawAttributes(['qty' => '5', 'price' => '10.5'], true);
        $model->qty = '7';

        $this->assertTrue($model->sameWithAndDirty('qty', 7, 'integer'));
        $this->assertFalse($model->sameWithAndDirty('qty', 7));
        $this->assertFalse($model->sameWithAndDirty('price', '10.50', 'decimal:2'));
        $this->assertTrue($model->notSameWithAndDirty('qty', 5, 'integer'));
        $this->assertFalse($model->notSameWithAndDirty('qty', 7, 'integer'));
        $this->assertFalse($model->notSameWithAndDirty('price', 1, 'integer'));
    }

    #[Test]
    public function it_compares_the_columns_with_another_model(): void
    {
        $comment = MorphComment::find(1);

        $this->assertTrue($comment->sameWithOther(MorphComment::find(2), 'commentable_type'));
        $this->assertFalse($comment->sameWithOther(MorphComment::find(2), 'commentable_type', 'commentable_id'));
        $this->assertFalse($comment->sameWithOther(MorphComment::find(3), 'commentable_id', 'commentable_type'));
        $this->assertTrue($comment->sameWithOther(MorphComment::find(3), 'commentable_id'));
    }

    #[Test]
    public function it_compares_strictly_with_another_model(): void
    {
        $other = MorphComment::find(1);
        $other->commentable_id = '1';

        $this->assertFalse(MorphComment::find(1)->sameWithOther($other, 'commentable_id'));
        $this->assertTrue(MorphComment::find(1)->sameWithOther(new MorphComment, 'missing'));
    }

    #[Test]
    public function it_is_not_same_with_another_model_without_columns(): void
    {
        $this->assertFalse(MorphComment::find(1)->sameWithOther(MorphComment::find(1)));
    }

    #[Test]
    public function it_checks_that_one_column_is_the_same_as_another_model(): void
    {
        $comment = MorphComment::find(1);

        $this->assertTrue($comment->sameWithOtherOr(MorphComment::find(1), 'commentable_type', 'commentable_id'));
        $this->assertTrue($comment->sameWithOtherOr(MorphComment::find(2), 'commentable_type', 'commentable_id'));
        $this->assertTrue($comment->sameWithOtherOr(MorphComment::find(2), 'commentable_id', 'commentable_type'));
        $this->assertFalse(MorphComment::find(2)->sameWithOtherOr(MorphComment::find(3), 'commentable_type', 'commentable_id'));
        $this->assertFalse($comment->sameWithOtherOr(MorphComment::find(1)));
    }

    #[Test]
    public function it_checks_that_every_column_is_not_the_same_as_another_model(): void
    {
        $comment = MorphComment::find(2);

        $this->assertTrue($comment->notSameWithOther(MorphComment::find(3), 'commentable_type', 'commentable_id'));
        $this->assertFalse($comment->notSameWithOther(MorphComment::find(1), 'commentable_id', 'commentable_type'));
        $this->assertFalse($comment->notSameWithOther(MorphComment::find(2), 'commentable_type', 'commentable_id'));
        $this->assertFalse($comment->notSameWithOther(MorphComment::find(3)));
    }

    #[Test]
    public function it_checks_that_one_column_is_not_the_same_as_another_model(): void
    {
        $comment = MorphComment::find(1);

        $this->assertTrue($comment->notSameWithOtherOr(MorphComment::find(2), 'commentable_id', 'commentable_type'));
        $this->assertTrue($comment->notSameWithOtherOr(MorphComment::find(2), 'commentable_type', 'commentable_id'));
        $this->assertFalse($comment->notSameWithOtherOr(MorphComment::find(1), 'commentable_type', 'commentable_id'));
        $this->assertFalse($comment->notSameWithOtherOr(MorphComment::find(2)));
    }

    #[Test]
    public function it_compares_strictly_when_it_is_not_the_same_as_another_model(): void
    {
        $other = MorphComment::find(1);
        $other->commentable_id = '1';

        $this->assertTrue(MorphComment::find(1)->notSameWithOther($other, 'commentable_id'));
        $this->assertTrue(MorphComment::find(1)->notSameWithOtherOr($other, 'commentable_id'));
        $this->assertFalse(MorphComment::find(1)->sameWithOtherOr($other, 'commentable_id'));
    }

    #[Test]
    public function it_filters_by_the_values_of_a_relation_column(): void
    {
        $this->createWhereInRelationTables();

        $this->assertSame([1], WhereInAuthor::whereInRelation('books', 'name', ['x'])->pluck('id')->all());
        $this->assertSame([1, 2], WhereInAuthor::whereInRelation('books', 'name', ['x', 'y'])->pluck('id')->all());
        $this->assertSame([2], WhereInBook::whereInRelation('author', 'name', ['b'])->pluck('id')->all());
    }

    #[Test]
    public function it_finds_nothing_for_no_relation_values(): void
    {
        $this->createWhereInRelationTables();

        $this->assertSame([], WhereInAuthor::whereInRelation('books', 'name', [])->pluck('id')->all());
    }

    #[Test]
    public function it_filters_by_a_nested_relation_column(): void
    {
        $this->createWhereInRelationTables();

        $this->assertSame([1], WhereInBook::whereInRelation('author.tags', 'name', ['t1'])->pluck('id')->all());
    }

    #[Test]
    public function it_filters_by_a_self_referencing_relation_column(): void
    {
        $this->createWhereInRelationTables();

        $this->assertSame([2], WhereInAuthor::whereInRelation('parent', 'name', ['a'])->pluck('id')->all());
        $this->assertSame([2], WhereInAuthor::whereInRelation('parent', 'id', [1])->pluck('id')->all());
    }

    #[Test]
    public function it_filters_by_a_many_to_many_relation_column(): void
    {
        $this->createWhereInRelationTables();

        $this->assertSame([1], WhereInAuthor::whereInRelation('tags', 'name', ['t1'])->pluck('id')->all());
        $this->assertSame([3], WhereInAuthor::whereInRelation('tags', 'tags.id', [2])->pluck('id')->all());
    }

    #[Test]
    public function it_fails_on_a_relation_column_that_is_also_on_the_pivot_table(): void
    {
        $this->createWhereInRelationTables();

        $this->expectException(QueryException::class);

        WhereInAuthor::whereInRelation('tags', 'id', [2])->get();
    }

    #[Test]
    public function it_filters_by_a_morph_to_relation_column(): void
    {
        $this->createWhereInRelationTables();

        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->morphs('owner');
        });

        DB::table('notes')->insert([
            ['owner_type' => WhereInAuthor::class, 'owner_id' => 1],
            ['owner_type' => WhereInAuthor::class, 'owner_id' => 2],
        ]);

        $this->assertSame([2], WhereInNote::whereInRelation('owner', 'name', ['b'])->pluck('id')->all());
    }

    #[Test]
    public function it_chains_the_relation_values_filter_with_or_where(): void
    {
        $this->createWhereInRelationTables();

        $authors = WhereInAuthor::where('id', 3)->orWhere->whereInRelation('books', 'name', ['x'])->pluck('id')->all();

        $this->assertSame([1, 3], $authors);
    }

    #[Test]
    public function it_returns_a_random_id(): void
    {
        $this->createWhereInRelationTables();

        $this->assertContains(WhereInAuthor::randomId(), [1, 2, 3]);
    }

    #[Test]
    public function it_returns_a_random_id_of_the_filtered_rows(): void
    {
        $this->createWhereInRelationTables();

        $this->assertSame(2, WhereInAuthor::randomId('id', 2));
        $this->assertSame(3, WhereInAuthor::randomId('id', '>', 2));
    }

    #[Test]
    public function it_returns_null_as_the_random_id_without_rows(): void
    {
        $this->createWhereInRelationTables();

        $this->assertNull(WhereInAuthor::randomId('id', 99));
    }

    #[Test]
    public function it_compacts_the_given_columns(): void
    {
        $comment = MorphComment::find(1);

        $this->assertSame(['id' => 1, 'commentable_id' => 1], $comment->compact('id', 'commentable_id'));
        $this->assertSame(['commentable_id' => 1, 'id' => 1], $comment->compact('commentable_id', 'id'));
        $this->assertSame(['id' => 1, 'post' => 1], $comment->compact('id', ['post' => 'commentable_id']));
        $this->assertSame(['id' => 1, 'post' => 1, 'key' => 1], $comment->compact(['id', 'post' => 'commentable_id'], key: 'id'));
    }

    #[Test]
    public function it_compacts_a_missing_column_as_null(): void
    {
        $this->assertSame(['missing' => null], MorphComment::find(1)->compact('missing'));
    }

    #[Test]
    public function it_compacts_nothing_without_columns(): void
    {
        $this->assertSame([], MorphComment::find(1)->compact());
    }

    #[Test]
    public function it_copies_relations_to_another_model_with_the_given_columns(): void
    {
        $this->createCopyTables();

        CopyOrder::find(1)->copyRelationsTo(CopyOrder::find(2), ['items' => ['product', 'qty']]);

        $this->assertSame(
            [['order_id' => 2, 'product' => 'a', 'qty' => 1, 'note' => null], ['order_id' => 2, 'product' => 'b', 'qty' => 2, 'note' => null]],
            DB::table('copy_items')->where('order_id', 2)->orderBy('id')->get(['order_id', 'product', 'qty', 'note'])->map(fn ($row) => (array) $row)->all()
        );
        $this->assertSame(2, DB::table('copy_items')->where('order_id', 1)->count());
    }

    #[Test]
    public function it_copies_nothing_from_an_empty_relation(): void
    {
        $this->createCopyTables();

        CopyOrder::find(2)->copyRelationsTo(CopyOrder::find(1), ['items' => ['product', 'qty']]);

        $this->assertSame(2, DB::table('copy_items')->count());
    }

    private function createCopyTables(): void
    {
        Schema::create('copy_orders', function (Blueprint $table) {
            $table->id();
        });
        Schema::create('copy_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id');
            $table->string('product');
            $table->integer('qty');
            $table->string('note')->nullable();
        });

        DB::table('copy_orders')->insert([['id' => 1], ['id' => 2]]);
        DB::table('copy_items')->insert([
            ['order_id' => 1, 'product' => 'a', 'qty' => 1, 'note' => 'x'],
            ['order_id' => 1, 'product' => 'b', 'qty' => 2, 'note' => 'y'],
        ]);
    }

    private function createWhereInRelationTables(): void
    {
        Schema::create('authors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('parent_id')->nullable();
        });
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id');
            $table->string('name');
        });
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('author_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id');
            $table->foreignId('tag_id');
        });

        DB::table('authors')->insert([
            ['name' => 'a', 'parent_id' => null],
            ['name' => 'b', 'parent_id' => 1],
            ['name' => 'c', 'parent_id' => 2],
        ]);
        DB::table('books')->insert([
            ['author_id' => 1, 'name' => 'x'],
            ['author_id' => 2, 'name' => 'y'],
        ]);
        DB::table('tags')->insert([['name' => 't1'], ['name' => 't2']]);
        DB::table('author_tag')->insert([
            ['author_id' => 1, 'tag_id' => 1],
            ['author_id' => 3, 'tag_id' => 2],
        ]);
    }
}

class MorphComment extends Model
{
    use AdditionalQueries;

    protected static string $builder = Builder::class;

    protected $table = 'comments';
}

class CastCompareModel extends Model
{
    use AdditionalQueries;

    protected $casts = ['flag' => 'boolean'];
}

class MorphPost extends Model {}

class MorphVideo extends Model {}

class WhereInAuthor extends Model
{
    use AdditionalQueries;

    protected static string $builder = Builder::class;

    protected $table = 'authors';

    public function books(): HasMany
    {
        return $this->hasMany(WhereInBook::class, 'author_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(WhereInTag::class, 'author_tag', 'author_id', 'tag_id');
    }
}

class WhereInBook extends Model
{
    use AdditionalQueries;

    protected static string $builder = Builder::class;

    protected $table = 'books';

    public function author(): BelongsTo
    {
        return $this->belongsTo(WhereInAuthor::class, 'author_id');
    }
}

class WhereInTag extends Model
{
    protected $table = 'tags';
}

class WhereInNote extends Model
{
    use AdditionalQueries;

    protected static string $builder = Builder::class;

    protected $table = 'notes';

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }
}

class CopyOrder extends Model
{
    use AdditionalQueries;

    public $timestamps = false;

    protected $table = 'copy_orders';

    public function items(): HasMany
    {
        return $this->hasMany(CopyItem::class, 'order_id');
    }
}

class CopyItem extends Model
{
    public $timestamps = false;

    protected $table = 'copy_items';

    protected $guarded = [];
}
