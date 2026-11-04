<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use JundFauz\EloquentKompact\Builder\Str;
use JundFauz\EloquentKompact\Column\ColumnListing;
use JundFauz\EloquentKompact\Model\Collection;
use JundFauz\EloquentKompact\Model\KompactModel;
use JundFauz\EloquentKompact\Model\KompactPivot;
use JundFauz\EloquentKompact\Model\KompactUser;
use JundFauz\EloquentKompact\Model\Traits\AutoFillable;
use JundFauz\EloquentKompact\Model\Traits\AutoRelation;
use JundFauz\EloquentKompact\Model\Traits\Kompact;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class KompactTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('kompact_parents', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->integer('qty')->default(0);
            $table->softDeletes();
        });
        Schema::create('kompact_children', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id');
            $table->softDeletes();
        });
        Schema::create('kompact_hards', function (Blueprint $table) {
            $table->id();
        });
        Schema::create('kompact_pivots', function (Blueprint $table) {
            $table->id();
            $table->softDeletes();
        });

        DB::table('kompact_parents')->insert([
            ['name' => 'a', 'qty' => 2],
            ['name' => 'b', 'qty' => 3],
        ]);
        DB::table('kompact_children')->insert([
            ['parent_id' => 1],
            ['parent_id' => 1],
            ['parent_id' => 2],
        ]);
        DB::table('kompact_hards')->insert(['id' => 1]);
        DB::table('kompact_pivots')->insert(['id' => 1]);
    }

    protected function tearDown(): void
    {
        Model::automaticallyEagerLoadRelationships(false);
        ColumnListing::flush();
        KompactModel::flushKompactCache();
        KompactUser::flushKompactCache();

        parent::tearDown();
    }

    public static function softDeleteModels(): array
    {
        return [
            'model' => [KompactTestParent::class],
            'user' => [KompactTestUser::class],
        ];
    }

    public static function hardDeleteModels(): array
    {
        return [
            'model' => [KompactTestHard::class],
            'user' => [KompactTestHardUser::class],
        ];
    }

    #[Test]
    public function it_gives_the_same_traits_to_the_model_and_the_user(): void
    {
        foreach ([Kompact::class, AutoFillable::class, AutoRelation::class] as $trait) {
            $this->assertContains($trait, class_uses_recursive(KompactModel::class));
            $this->assertContains($trait, class_uses_recursive(KompactUser::class));
        }

        $this->assertInstanceOf(\Illuminate\Foundation\Auth\User::class, new KompactTestUser);
    }

    #[Test]
    public function it_only_allows_the_user_to_be_extended(): void
    {
        $this->assertTrue((new \ReflectionClass(KompactUser::class))->isAbstract());
    }

    /**
     * PHP before 8.5 binds a trait after the parent class, so a different default value of a
     * property of Model is a fatal error there, which a run on PHP 8.5 alone never shows.
     */
    #[Test]
    public function it_does_not_declare_a_property_of_model_in_the_trait(): void
    {
        $modelProperties = array_map(
            fn (\ReflectionProperty $property) => $property->getName(),
            (new \ReflectionClass(\Illuminate\Foundation\Auth\User::class))->getProperties(),
        );

        foreach ((new \ReflectionClass(Kompact::class))->getProperties() as $property) {
            $this->assertNotContains($property->getName(), $modelProperties);
        }
    }

    #[Test]
    #[DataProvider('softDeleteModels')]
    public function it_uses_the_kompact_collection_for_the_model_and_the_user(string $model): void
    {
        $this->assertInstanceOf(Collection::class, $model::all());
    }

    #[Test]
    #[DataProvider('softDeleteModels')]
    public function it_soft_deletes_by_default(string $model): void
    {
        $model::first()->delete();

        $this->assertSame(1, $model::count());
        $this->assertSame(2, DB::table('kompact_parents')->count());
    }

    #[Test]
    #[DataProvider('softDeleteModels')]
    public function it_restores_a_soft_deleted_row(string $model): void
    {
        $model::first()->delete();
        $model::withTrashed()->first()->restore();

        $this->assertSame(2, $model::count());
    }

    #[Test]
    #[DataProvider('softDeleteModels')]
    public function it_force_deletes_a_row(string $model): void
    {
        $model::first()->forceDelete();

        $this->assertSame(1, DB::table('kompact_parents')->count());
    }

    #[Test]
    #[DataProvider('hardDeleteModels')]
    public function it_deletes_the_row_when_soft_deletes_are_off(string $model): void
    {
        $model::first()->delete();

        $this->assertSame(0, DB::table('kompact_hards')->count());
    }

    #[Test]
    public function it_deletes_the_row_when_soft_deletes_are_off_on_a_table_with_the_column(): void
    {
        KompactTestHardWithColumn::first()->delete();

        $this->assertSame(1, DB::table('kompact_parents')->count());
    }

    #[Test]
    public function it_fires_the_deleted_event_when_soft_deletes_are_off(): void
    {
        $deleted = 0;
        KompactTestHard::deleted(function () use (&$deleted) {
            $deleted++;
        });

        KompactTestHard::first()->delete();

        $this->assertSame(1, $deleted);
    }

    #[Test]
    public function it_counts_for_a_filter_on_the_model_and_the_user(): void
    {
        $parent = KompactTestParent::getCountForFilter('name');
        $user = KompactTestUser::getCountForFilter('name');

        $this->assertInstanceOf(KompactTestParent::class, $parent);
        $this->assertSame(2, (int) $parent->name_count);
        $this->assertInstanceOf(KompactTestUser::class, $user);
        $this->assertSame(2, (int) $user->name_count);
    }

    #[Test]
    public function it_flushes_the_cache_of_the_user_after_migrating(): void
    {
        Schema::create('kompact_flush_users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable();
        });

        $this->assertSame(['email'], (new KompactTestFlushUser)->getFillable());

        Schema::table('kompact_flush_users', function (Blueprint $table) {
            $table->string('phone')->nullable();
        });
        event(new MigrationsEnded('up'));

        $this->assertSame(['email', 'phone'], (new KompactTestFlushUser)->getFillable());
    }

    #[Test]
    public function it_uses_the_kompact_collection_for_model_results(): void
    {
        $this->assertInstanceOf(Collection::class, KompactTestParent::all());
        $this->assertInstanceOf(Collection::class, KompactTestParent::where('id', 1)->get());
        $this->assertInstanceOf(Collection::class, KompactTestParent::first()->kompactTestChildren);
        $this->assertInstanceOf(Collection::class, KompactTestParent::with('kompactTestChildren')->first()->kompactTestChildren);
        $this->assertInstanceOf(Collection::class, KompactTestUser::all());
        $this->assertInstanceOf(Collection::class, KompactTestPivot::all());
        $this->assertInstanceOf(EloquentCollection::class, KompactTestParent::all());
    }

    #[Test]
    public function it_lets_a_model_choose_another_collection(): void
    {
        $this->assertInstanceOf(KompactTestOtherCollection::class, KompactTestCollectedBy::all());
        $this->assertInstanceOf(KompactTestOtherCollection::class, KompactTestOwnCollection::all());
    }

    #[Test]
    public function it_keeps_automatic_eager_loading(): void
    {
        Model::automaticallyEagerLoadRelationships();
        DB::enableQueryLog();
        DB::flushQueryLog();

        foreach (KompactTestParent::all() as $parent) {
            $parent->kompactTestChildren;
        }

        $dataQueries = array_filter(DB::getQueryLog(), fn (array $log) => str_starts_with($log['query'], 'select * from'));

        $this->assertCount(2, $dataQueries);

        DB::disableQueryLog();
    }

    #[Test]
    public function it_keeps_the_eloquent_methods_of_the_collection(): void
    {
        $parents = KompactTestParent::all();

        $this->assertSame(1, $parents->find(1)->id);
        $this->assertSame([1, 2], $parents->modelKeys());
        $this->assertTrue($parents->contains(1));
        $this->assertInstanceOf(Collection::class, $parents->fresh());
        $this->assertInstanceOf(Collection::class, $parents->load('kompactTestChildren'));
        $this->assertCount(2, $parents->first()->kompactTestChildren);
        $this->assertSame([2], $parents->diff(KompactTestParent::where('id', 1)->get())->modelKeys());
    }

    #[Test]
    public function it_has_the_kompact_methods_on_the_collection(): void
    {
        $parents = KompactTestParent::all();

        $this->assertCount(2, $parents->filterFilled());
        $this->assertInstanceOf(Collection::class, $parents->filterFilled());
        $this->assertCount(2, $parents->deleteNull());
        $this->assertSame(['qty' => 5], $parents->sumAndCompact('qty'));
        $this->assertSame(1, $parents->getOr('0')->id);
    }

    #[Test]
    public function it_compacts_the_first_model_of_the_collection(): void
    {
        $parents = KompactTestParent::all();

        $this->assertSame(['name' => 'a', 'qty' => 2], $parents->firstAndCompact('name', 'qty'));
        $this->assertSame(['qty' => 2, 'name' => 'a'], $parents->firstAndCompact('qty', 'name'));
        $this->assertSame(['name' => 'b'], $parents->where('name', 'b')->firstAndCompact('name'));
    }

    #[Test]
    public function it_compacts_a_missing_column_of_the_first_model_as_null(): void
    {
        $this->assertSame(['missing' => null], KompactTestParent::all()->firstAndCompact('missing'));
    }

    #[Test]
    public function it_compacts_nothing_without_columns_on_the_collection(): void
    {
        $this->assertSame([], KompactTestParent::all()->firstAndCompact());
    }

    #[Test]
    public function it_compacts_null_values_from_an_empty_collection(): void
    {
        $parents = KompactTestParent::where('id', 99)->get();

        $this->assertSame(['name' => null, 'qty' => null], $parents->firstAndCompact('name', 'qty'));
        $this->assertSame([], $parents->firstAndCompact());
    }

    #[Test]
    public function it_returns_a_model_or_a_str_from_get(): void
    {
        $parents = KompactTestParent::all();
        $default = $parents->get(9, 'none');

        $this->assertInstanceOf(KompactTestParent::class, $parents->get(0));
        $this->assertInstanceOf(Str::class, $default);
        $this->assertSame('none', $default->toString());
        $this->assertNull($parents->get(9));
    }

    #[Test]
    public function it_gets_a_base_collection_from_results_that_are_not_models(): void
    {
        $parents = KompactTestParent::all();

        $this->assertSame(BaseCollection::class, get_class($parents->pluck('name')));
        $this->assertSame(BaseCollection::class, get_class($parents->map(fn (KompactTestParent $parent) => $parent->name)));
        $this->assertSame(BaseCollection::class, get_class(KompactTestParent::query()->pluck('name')));
    }
}

class KompactTestOtherCollection extends EloquentCollection {}

class KompactTestParent extends KompactModel
{
    public $timestamps = false;

    protected $table = 'kompact_parents';

    public function kompactTestChildren(): HasMany
    {
        return $this->hasMany(KompactTestChild::class, 'parent_id');
    }
}

class KompactTestChild extends KompactModel
{
    public $timestamps = false;

    protected $table = 'kompact_children';

    public function kompactTestParent(): BelongsTo
    {
        return $this->belongsTo(KompactTestParent::class, 'parent_id');
    }
}

class KompactTestHard extends KompactModel
{
    public $timestamps = false;

    protected $table = 'kompact_hards';

    protected static bool $useSoftDelete = false;
}

class KompactTestHardWithColumn extends KompactModel
{
    public $timestamps = false;

    protected $table = 'kompact_parents';

    protected static bool $useSoftDelete = false;
}

class KompactTestUser extends KompactUser
{
    public $timestamps = false;

    protected $table = 'kompact_parents';
}

class KompactTestHardUser extends KompactUser
{
    public $timestamps = false;

    protected $table = 'kompact_hards';

    protected static bool $useSoftDelete = false;
}

class KompactTestFlushUser extends KompactUser
{
    public $timestamps = false;

    protected $table = 'kompact_flush_users';
}

class KompactTestPivot extends KompactPivot
{
    public $timestamps = false;

    protected $table = 'kompact_pivots';
}

#[CollectedBy(KompactTestOtherCollection::class)]
class KompactTestCollectedBy extends KompactModel
{
    protected $table = 'kompact_parents';
}

class KompactTestOwnCollection extends KompactModel
{
    protected $table = 'kompact_parents';

    protected static string $collectionClass = KompactTestOtherCollection::class;
}
