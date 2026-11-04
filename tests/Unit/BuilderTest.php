<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use JundFauz\EloquentKompact\Builder\Collection;
use JundFauz\EloquentKompact\Model\Builder;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class BuilderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('builder_posts', function (Blueprint $table) {
            $table->id();
            $table->integer('a')->default(0);
            $table->integer('b')->default(0);
            $table->integer('c')->default(0);
            $table->string('m_type')->nullable();
        });
        Schema::create('builder_kids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id');
            $table->string('name');
        });

        DB::table('builder_posts')->insert([
            ['a' => 1, 'b' => 0, 'c' => 0, 'm_type' => 'x'],
            ['a' => 0, 'b' => 1, 'c' => 9, 'm_type' => BuilderTestPost::class],
            ['a' => 0, 'b' => 0, 'c' => 9, 'm_type' => BuilderTestPost::class],
        ]);
        DB::table('builder_kids')->insert(['post_id' => 2, 'name' => 'k']);
    }

    public static function methods(): array
    {
        return [
            'whereSameWith' => [fn (Builder $query) => $query->whereSameWith((new BuilderTestPost)->forceFill(['c' => 9]), 'c')],
            'whereMorphType' => [fn (Builder $query) => $query->whereMorphType('m', BuilderTestPost::class)],
            'whereMorphTypeNot' => [fn (Builder $query) => $query->whereMorphTypeNot('m', 'x')],
            'whereInRelation' => [fn (Builder $query) => $query->whereInRelation('kids', 'name', ['k'])],
        ];
    }

    #[Test]
    #[DataProvider('methods')]
    public function it_groups_an_earlier_or_where(Closure $method): void
    {
        $query = $method(BuilderTestPost::where('a', 1)->orWhere('b', 1));

        $this->assertStringContainsString('where ("a" = ? or "b" = ?) and ', $query->toSql());
        $this->assertSame([2], $query->pluck('id')->all());
    }

    #[Test]
    #[DataProvider('methods')]
    public function it_returns_the_builder(Closure $method): void
    {
        $this->assertInstanceOf(Builder::class, $method(BuilderTestPost::query()));
    }

    #[Test]
    public function it_does_not_group_earlier_conditions_without_an_or(): void
    {
        $sql = BuilderTestPost::where('a', 1)->where('b', 1)->whereMorphType('m', BuilderTestPost::class)->toSql();

        $this->assertStringContainsString('where "a" = ? and "b" = ? and "m_type" = ?', $sql);
    }

    #[Test]
    public function it_filters_on_a_related_model_without_the_builder(): void
    {
        $this->assertSame([2], BuilderTestPost::whereInRelation('kids', 'name', ['k'])->pluck('id')->all());
    }

    #[Test]
    public function it_filters_on_a_related_model_with_the_builder(): void
    {
        $this->assertSame([2], BuilderTestPost::whereInRelation('ownKids', 'name', ['k'])->pluck('id')->all());
    }

    #[Test]
    public function it_works_on_a_relation(): void
    {
        $kids = BuilderTestPost::find(2)->ownKids()->whereSameWith((new BuilderTestKid)->forceFill(['name' => 'k']), 'name');

        $this->assertSame(1, $kids->count());
    }

    #[Test]
    public function it_plucks_a_builder_collection(): void
    {
        $this->assertSame(Collection::class, get_class(BuilderTestPost::pluck('a')));
        $this->assertSame(Collection::class, get_class(BuilderTestPost::where('a', 1)->pluck('a')));
        $this->assertSame(Collection::class, get_class(BuilderTestPost::find(2)->ownKids()->pluck('name')));
    }

    #[Test]
    public function it_keeps_the_keys_and_the_casts_when_plucking(): void
    {
        $this->assertSame([1 => '1', 2 => '0', 3 => '0'], BuilderTestPost::pluck('a', 'id')->all());
    }

    #[Test]
    public function it_leaves_the_pluck_of_a_related_model_without_the_builder_as_it_is(): void
    {
        $this->assertSame(BaseCollection::class, get_class(BuilderTestPost::find(2)->kids()->pluck('name')));
    }
}

class BuilderTestPost extends Model
{
    public $timestamps = false;

    protected static string $builder = Builder::class;

    protected $table = 'builder_posts';

    protected $casts = ['a' => 'string'];

    public function kids(): HasMany
    {
        return $this->hasMany(BuilderTestPlainKid::class, 'post_id');
    }

    public function ownKids(): HasMany
    {
        return $this->hasMany(BuilderTestKid::class, 'post_id');
    }
}

class BuilderTestKid extends Model
{
    public $timestamps = false;

    protected static string $builder = Builder::class;

    protected $table = 'builder_kids';
}

class BuilderTestPlainKid extends Model
{
    public $timestamps = false;

    protected $table = 'builder_kids';
}
