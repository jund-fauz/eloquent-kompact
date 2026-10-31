<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use JundFauz\EloquentKompact\Model\Traits\StaticH;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class StaticHTest extends TestCase
{
    protected function tearDown(): void
    {
        Relation::morphMap([], false);

        parent::tearDown();
    }

    #[Test]
    public function it_returns_the_table_name(): void
    {
        $this->assertSame('static_h_posts', StaticHPost::tableName());
    }

    #[Test]
    public function it_returns_the_class_as_morph_class(): void
    {
        $this->assertSame(StaticHPost::class, StaticHPost::morphClass());
    }

    #[Test]
    public function it_returns_the_morph_alias_as_morph_class(): void
    {
        Relation::morphMap(['post' => StaticHPost::class]);

        $this->assertSame('post', StaticHPost::morphClass());
    }
}

class StaticHPost extends Model
{
    use StaticH;
}
