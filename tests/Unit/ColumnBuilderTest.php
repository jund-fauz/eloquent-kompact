<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use BadMethodCallException;
use JundFauz\EloquentKompact\Column\Builder\Concerns\ColumnBuilder;
use JundFauz\EloquentKompact\Column\Builder\Concerns\Morph;
use JundFauz\EloquentKompact\Column\Builder\Concerns\RelationBuilder;
use JundFauz\EloquentKompact\Column\ColumnCreator;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ColumnBuilderTest extends TestCase
{
    protected function tearDown(): void
    {
        ColumnCreator::configureDelimiter('_');

        parent::tearDown();
    }

    #[Test]
    public function it_builds_the_column_of_its_token(): void
    {
        $this->assertSame('net_weight_gram', Gram::netWeight());
        $this->assertSame('tags_array', ArrayC::tags());
    }

    #[Test]
    public function it_builds_the_column_of_a_custom_token(): void
    {
        config(['eloquent-kompact.column.custom_tokens' => ['per_unit' => 'pu']]);

        $this->assertSame('price_pu', PerUnit::price());
    }

    #[Test]
    public function it_builds_the_morph_columns_by_the_constant_or_the_morph_name(): void
    {
        $this->assertSame('attributable_id', TestMorph::attributeId());
        $this->assertSame('attributable_type', TestMorph::attributeModel());
        $this->assertSame('attributable_id', TestMorph::attributableId());
        $this->assertSame('attributable_type', TestMorph::attributableModel());
    }

    #[Test]
    public function it_uses_the_delimiter(): void
    {
        ColumnCreator::configureDelimiter('-');

        $this->assertSame('net_weight-gram', Gram::netWeight());
        $this->assertSame('attributable-id', TestMorph::attributeId());
        $this->assertSame('attributable-type', TestMorph::attributeModel());
    }

    #[Test]
    public function it_rejects_an_unknown_morph(): void
    {
        $this->expectException(BadMethodCallException::class);

        TestMorph::sourceId();
    }

    #[Test]
    public function it_builds_the_relation_columns_by_their_name_or_their_alias(): void
    {
        $this->assertSame('customer_user_id', TestRelation::customer_user());
        $this->assertSame('author_user_id', TestRelation::author());
    }

    #[Test]
    public function it_uses_the_delimiter_for_the_relation_columns(): void
    {
        ColumnCreator::configureDelimiter('-');

        $this->assertSame('author_user-id', TestRelation::author());
    }

    #[Test]
    public function it_rejects_an_unknown_relation(): void
    {
        $this->expectException(BadMethodCallException::class);

        TestRelation::author_user();
    }
}

class Gram extends ColumnBuilder
{
}

class ArrayC extends ColumnBuilder
{
}

class PerUnit extends ColumnBuilder
{
}

class TestMorph extends Morph
{
    public const string attribute = 'attributable';
}

class TestRelation extends RelationBuilder
{
    protected static array $relation = ['customer_user', 'author' => 'author_user'];
}
