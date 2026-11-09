<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use JundFauz\EloquentKompact\Column\ColumnListing;
use JundFauz\EloquentKompact\Model\KompactModel;
use JundFauz\EloquentKompact\Regex\Regex;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AutoFillableTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        ColumnListing::flush();
        KompactModel::flushKompactCache();

        Schema::create('af_items', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('secret_note')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        ColumnListing::flush();
        KompactModel::flushKompactCache();

        parent::tearDown();
    }

    #[Test]
    public function it_fills_every_column_but_the_id_and_the_timestamps(): void
    {
        $this->assertSame(['name', 'secret_note', 'user_id'], (new AutoFillableItem)->getFillable());
    }

    #[Test]
    public function it_excludes_the_exact_columns(): void
    {
        config(['eloquent-kompact.fillable.exclude_column_by.exact' => ['id', 'name']]);

        $this->assertSame(['secret_note', 'user_id'], (new AutoFillableItem)->getFillable());
    }

    #[Test]
    public function it_excludes_the_columns_by_their_token(): void
    {
        config(['eloquent-kompact.fillable.exclude_column_by.token' => ['relation']]);

        $this->assertSame(['name', 'secret_note'], (new AutoFillableItem)->getFillable());
    }

    #[Test]
    public function it_excludes_the_columns_by_a_regex(): void
    {
        config(['eloquent-kompact.fillable.exclude_column_by.regex' => [Regex::create('secret_')->startsWith()]]);

        $this->assertSame(['name', 'user_id'], (new AutoFillableItem)->getFillable());
    }

    #[Test]
    public function it_works_without_every_key_of_the_config(): void
    {
        config(['eloquent-kompact.fillable.exclude_column_by' => ['exact' => ['id']]]);

        $this->assertSame(['name', 'secret_note', 'user_id'], (new AutoFillableItem)->getFillable());
    }

    #[Test]
    public function it_works_with_the_config_of_an_older_version(): void
    {
        config(['eloquent-kompact.fillable' => ['excluded_columns_by_regex' => []]]);

        $this->assertSame(['id', 'name', 'secret_note', 'user_id'], (new AutoFillableItem)->getFillable());
    }

    #[Test]
    public function it_includes_the_timestamps_when_asked(): void
    {
        $this->assertSame(
            ['name', 'secret_note', 'user_id', 'paid_at', 'created_at', 'updated_at'],
            (new AutoFillableTimestampItem)->getFillable(),
        );
    }

    #[Test]
    public function it_keeps_the_declared_fillable(): void
    {
        $this->assertSame(['name', 'secret_note', 'user_id', 'paid_at'], (new AutoFillableDeclaredItem)->getFillable());
    }
}

class AutoFillableItem extends KompactModel
{
    protected $table = 'af_items';
}

class AutoFillableTimestampItem extends KompactModel
{
    protected $table = 'af_items';

    protected static bool $includeTimestampColumn = true;
}

class AutoFillableDeclaredItem extends KompactModel
{
    protected $table = 'af_items';

    protected $fillable = ['paid_at'];
}
