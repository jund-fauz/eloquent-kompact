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
    public function it_gets_the_first_non_null_attribute_or_default_with_get_or(): void
    {
        $model = (new MorphComment)->forceFill([
            'nickname' => null,
            'name' => 'Ana',
            'zero' => 0,
            'empty' => '',
            'off' => false,
        ]);

        $this->assertSame('Ana', $model->getOr('nickname', 'name'));
        $this->assertSame(0, $model->getOr('nickname', 'zero', 'name'));
        $this->assertSame('', $model->getOr('nickname', 'empty', 'name'));
        $this->assertFalse($model->getOr('nickname', 'off', 'name'));
        $this->assertNull($model->getOr('nickname', 'missing'));
        $this->assertNull($model->getOr());

        $withFallback = $model->withDefault('-');
        $this->assertSame('-', $withFallback->getOr('nickname', 'missing'));
        $this->assertSame('-', $withFallback->getOr());
        $this->assertSame('Ana', $withFallback->getOr('nickname', 'name'));
        $this->assertSame(0, $withFallback->getOr('nickname', 'zero'));
        $this->assertSame('N/A', $withFallback->withDefault('N/A')->getOr('missing'));
        $this->assertSame('-', $withFallback->getOr('missing'));

        // Original model remains untouched
        $this->assertNull($model->getOr('nickname', 'missing'));
        $this->assertNull($model->getOr());
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
    public function it_uses_the_default_from_with_default_in_get_many(): void
    {
        $model = (new MorphComment)->forceFill([
            'name' => 'Ana',
            'nickname' => null,
            'zero' => 0,
            'empty' => '',
            'off' => false,
        ]);

        $this->assertSame(
            ['Ana', '-', 0, '', false, '-'],
            $model->withDefault('-')->getMany('name', 'nickname', 'zero', 'empty', 'off', 'missing'),
        );
        $this->assertSame([], $model->withDefault('-')->getMany());
        $this->assertSame([null, null], $model->getMany('nickname', 'missing'));
    }

    #[Test]
    public function it_checks_that_an_attribute_is_not_the_value(): void
    {
        $comment = MorphComment::find(3);

        $this->assertFalse($comment->not('same_with', 'commentable_type', 'video'));
        $this->assertTrue($comment->not('same_with', 'commentable_type', MorphPost::class));
        $this->assertTrue($comment->not('same_with', 'commentable_id', '1'));
        $this->assertFalse($comment->not('same_with', 'missing', null));
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
    public function it_assigns_conditionally_with_single_and_array_columns(): void
    {
        $comment = (new MorphComment)->forceFill([
            'commentable_type' => 'video',
            'commentable_id' => '   ',
            'zero' => 0,
            'zero_str' => '0',
            'off' => false,
        ]);

        $comment->assign('commentable_type', 'post', 'blank');
        $comment->assign('commentable_id', 5, 'blank');
        $comment->assign('missing', 'x', 'blank');
        $comment->assign(['zero' => 9, 'zero_str' => '9', 'off' => true], 'blank');

        $this->assertSame(
            ['video', 5, 'x', 0, '0', false],
            $comment->getMany('commentable_type', 'commentable_id', 'missing', 'zero', 'zero_str', 'off'),
        );

        // Named condition: argument on an array of columns (including '' and 0 which are not null)
        $named = (new MorphComment)->forceFill(['a' => 'keep', 'b' => null, 'empty_str' => '', 'zero' => 0]);
        $named->assign(['a' => 'replaced', 'b' => 'set', 'empty_str' => 'replaced', 'zero' => 5], condition: 'null');
        $this->assertSame(['keep', 'set', '', 0], $named->getMany('a', 'b', 'empty_str', 'zero'));

        // Keyed checks, including a null check value (must not strip ['same_with' => null])
        $keyed = new MorphComment;
        $keyed->setRawAttributes(['status' => 'draft', 'note' => 'keep', 'empty_note' => null], true);
        $keyed->status = 'pending';

        $keyed->assign('status', 'paid', ['dirty', 'in' => 'draft,pending']);
        $keyed->assign('note', 'overwritten', ['same_with' => null]);
        $keyed->assign('empty_note', 'filled_in', ['same_with' => null]);
        $keyed->assign(['note' => 'still_kept'], ['same_with' => null]);

        $this->assertSame(['paid', 'keep', 'filled_in'], $keyed->getMany('status', 'note', 'empty_note'));

        // Positional null items in $condition are ignored
        $positionalNull = (new MorphComment)->forceFill(['a' => 'old', 'b' => 'keep', 'c' => '   ']);
        $positionalNull->assign('a', 'new', [null]);
        $positionalNull->assign(['b' => 'overwritten', 'c' => 'trimmed'], [null, 'blank']);
        $this->assertSame(['new', 'keep', 'trimmed'], $positionalNull->getMany('a', 'b', 'c'));
    }

    #[Test]
    public function it_assigns_from_another_model_conditionally(): void
    {
        $other = (new MorphComment)->forceFill([
            'commentable_type' => 'video',
            'commentable_id' => 9,
            'note' => 'from_other',
        ]);

        $blankTarget = (new MorphComment)->forceFill([
            'commentable_type' => '   ',
            'commentable_id' => 0,
            'note' => 'keep',
        ]);
        $blankTarget->assignFromIf('blank', $other, 'commentable_type', 'commentable_id', 'note');
        $this->assertSame(['video', 0, 'keep'], $blankTarget->getMany('commentable_type', 'commentable_id', 'note'));

        $nullTarget = (new MorphComment)->forceFill([
            'commentable_type' => null,
            'commentable_id' => 0,
            'note' => '',
        ]);
        $nullTarget->assignFromIf('null', $other, 'commentable_type', 'commentable_id', 'note');
        $this->assertSame(['video', 0, ''], $nullTarget->getMany('commentable_type', 'commentable_id', 'note'));

        $dirtyTarget = new MorphComment;
        $dirtyTarget->setRawAttributes(['commentable_type' => '', 'commentable_id' => 1, 'note' => null], true);
        $dirtyTarget->commentable_type = '   ';
        $dirtyTarget->assignFromIf(['dirty', 'blank'], $other, 'commentable_type', 'note');
        $this->assertSame(['video', null], $dirtyTarget->getMany('commentable_type', 'note'));

        $nullCheckTarget = (new MorphComment)->forceFill(['commentable_type' => 'post', 'note' => null]);
        $nullCheckTarget->assignFromIf(['same_with' => null], $other, 'commentable_type', 'note');
        $this->assertSame(['post', 'from_other'], $nullCheckTarget->getMany('commentable_type', 'note'));

        // Nullable $condition and positional null items in $condition
        $positionalNullTarget = (new MorphComment)->forceFill(['commentable_type' => 'post', 'note' => '   ']);
        $positionalNullTarget->assignFromIf([null, 'blank'], $other, 'commentable_type', 'note');
        $this->assertSame(['post', 'from_other'], $positionalNullTarget->getMany('commentable_type', 'note'));

        $positionalNullTarget->assignFromIf([null], $other, 'commentable_type');
        $this->assertSame('video', $positionalNullTarget->commentable_type);

        $nullCondTarget = (new MorphComment)->forceFill(['commentable_type' => 'post']);
        $nullCondTarget->assignFromIf(null, $other, 'commentable_type');
        $this->assertSame('video', $nullCondTarget->commentable_type);
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
        $this->assertTrue($model->not('same_with', 'qty', 5));
        $this->assertFalse($model->not('same_with', 'qty', 5, 'integer'));
        $this->assertTrue($model->not('same_with', 'qty', 6, 'integer'));

        // The casts of the model stay as they are
        $this->assertSame('5', $model->qty);
        $this->assertTrue($model->flag);
        $this->assertArrayNotHasKey('qty', $model->getCasts());
    }

    #[Test]
    public function it_greps_a_column_or_the_default(): void
    {
        $model = new CastCompareModel();
        $model->setRawAttributes(['qty' => '5', 'note' => null, 'flag' => 1]);

        $this->assertSame('5', $model->grep('qty', 0));
        $this->assertSame(5, $model->grep('qty', 0, 'integer'));
        $this->assertTrue($model->grep('flag', false));
        $this->assertSame('-', $model->grep('note', '-'));
        $this->assertSame('-', $model->grep('missing', '-'));
        $this->assertSame(0, $model->grep('note', '0', 'integer'));
        $this->assertNull($model->grep('note', null, 'integer'));

        // The casts of the model stay as they are
        $this->assertSame('5', $model->qty);
        $this->assertArrayNotHasKey('qty', $model->getCasts());
    }

    #[Test]
    public function it_compares_a_dirty_column_with_another_cast(): void
    {
        $model = new CastCompareModel();
        $model->setRawAttributes(['qty' => '5', 'price' => '10.5'], true);
        $model->qty = '7';

        $this->assertTrue($model->isAllOf('qty', 'dirty', sameWith: [7, 'integer']));
        $this->assertFalse($model->isAllOf('qty', 'dirty', sameWith: [7]));
        $this->assertFalse($model->isAllOf('price', 'dirty', sameWith: ['10.50', 'decimal:2']));
        $this->assertTrue($model->isAllOf('qty', 'dirty', not_same_with: [5, 'integer']));
        $this->assertFalse($model->isAllOf('qty', 'dirty', not_same_with: [7, 'integer']));
        $this->assertFalse($model->isAllOf('price', 'dirty', not_same_with: [1, 'integer']));
    }

    #[Test]
    public function it_casts_the_value_with_the_cast_too(): void
    {
        $model = new CastCompareModel();
        $model->setRawAttributes(['qty' => '5', 'price' => '10.5'], true);
        $model->qty = '7';

        $this->assertTrue($model->sameWith('price', '10.5', 'decimal:2'));
        $this->assertFalse($model->not('same_with', 'price', '10.5', 'decimal:2'));
        $this->assertTrue($model->sameWith('qty', '7', 'integer'));
        $this->assertFalse($model->not('same_with', 'qty', '7', 'integer'));
        $this->assertTrue($model->not('same_with', 'qty', '5', 'integer'));
        $this->assertTrue($model->isAllOf('qty', 'dirty', sameWith: ['7', 'integer']));
        $this->assertFalse($model->isAllOf('qty', 'dirty', not_same_with: ['7', 'integer']));

        // Without a cast the value is compared as it is
        $this->assertFalse($model->sameWith('qty', 7));
        $this->assertTrue($model->not('same_with', 'qty', 7));
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
        $this->assertFalse(MorphComment::find(1)->sameWithOther($other, 'commentable_id', 'or'));
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

        $this->assertTrue($comment->sameWithOther(MorphComment::find(1), 'commentable_type', 'commentable_id', 'or'));
        $this->assertTrue($comment->sameWithOther(MorphComment::find(2), 'commentable_type', 'commentable_id', 'or'));
        $this->assertTrue($comment->sameWithOther(MorphComment::find(2), 'commentable_id', 'commentable_type', 'or'));
        $this->assertFalse(MorphComment::find(2)->sameWithOther(MorphComment::find(3), 'commentable_type', 'commentable_id', 'or'));
        $this->assertFalse($comment->sameWithOther(MorphComment::find(1), 'or'));
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
    public function it_compacts_the_foreign_key_and_primary_key(): void
    {
        $model = (new CompactIdModel)->forceFill(['id' => 7]);

        $this->assertSame(['compact_id_model_id' => 7], $model->compactId());
        $this->assertSame(['compact_id_model_id' => null], (new CompactIdModel)->compactId());
    }

    #[Test]
    public function it_processes_matching_columns_with_string_and_array_actions(): void
    {
        $model = (new ProcessModel)->forceFill(['a' => null, 'b' => 'keep', 'c' => 'foo']);

        $model->process(['assign' => 'default'], ['a', 'b'], 'null');
        $this->assertSame('default', $model->a);
        $this->assertSame('keep', $model->b);

        $model->process('clear_column', ['a', 'b'], ['filled', 'same_with' => 'default']);
        $this->assertNull($model->a);
        $this->assertSame('keep', $model->b);

        $model->process(['append_pair' => ['_1', '_2']], ['b', 'c'], ['same_with' => 'foo']);
        $this->assertSame('keep', $model->b);
        $this->assertSame('foo_1_2', $model->c);
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

    #[Test]
    public function it_checks_a_single_condition_on_a_column_with_if(): void
    {
        $model = new CastCompareModel;
        $model->setRawAttributes(['qty' => '5', 'note' => '   ', 'missing_col' => null], true);
        $model->qty = '12';

        $this->assertTrue($model->if('dirty', 'qty'));
        $this->assertFalse($model->if('dirty', 'note'));
        $this->assertTrue($model->if('blank', 'note'));
        $this->assertTrue($model->if('numeric', 'qty'));
        $this->assertTrue($model->if('null', 'missing_col'));
        $this->assertTrue($model->if('not_null', 'qty'));
    }

    #[Test]
    public function it_compares_a_column_against_an_array_of_values_unless_the_column_itself_is_an_array(): void
    {
        $model = new CastCompareModel;
        $model->setRawAttributes([
            'status' => 'paid',
            'qty' => '5',
            'tags' => '["a","b"]',
        ]);
        $model->mergeCasts(['tags' => 'array']);

        $this->assertTrue($model->sameWith('status', ['draft', 'paid']));
        $this->assertFalse($model->sameWith('status', ['draft', 'sent']));
        $this->assertFalse($model->sameWith('status', []));
        $this->assertTrue($model->sameWith('qty', [3, 5], 'integer'));
        $this->assertFalse($model->sameWith('qty', [3, 4], 'integer'));

        // Array-cast column compares against the array as a whole
        $this->assertTrue($model->sameWith('tags', ['a', 'b']));
        $this->assertFalse($model->sameWith('tags', ['a']));

        $other = new CastCompareModel;
        $other->setRawAttributes(['tags' => '["a","b"]']);
        $other->mergeCasts(['tags' => 'array']);
        $this->assertTrue($model->sameWithOther($other, 'tags'));
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

class CompactIdModel extends Model
{
    use AdditionalQueries, \JundFauz\EloquentKompact\Model\Traits\StaticH;
}

class ProcessModel extends Model
{
    use AdditionalQueries;

    public function clearColumn(string $column): void
    {
        $this->{$column} = null;
    }

    public function appendPair(string $column, string $first, string $second): void
    {
        $this->{$column} .= $first.$second;
    }
}
