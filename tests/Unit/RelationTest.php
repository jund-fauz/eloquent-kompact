<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
use JundFauz\EloquentKompact\Builder\Relation;
use JundFauz\EloquentKompact\Relation\Arrange;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RelationTest extends TestCase
{
    #[Test]
    public function it_builds_the_path_in_chain_order(): void
    {
        $this->assertSame('relationPosts.relationUser.name', Relation::arrange('name')->relations(RelationPost::class)->relation(RelationUser::class)->build());
        $this->assertSame('relationUser.relationPosts.name', Relation::arrange('name')->relation(RelationUser::class)->relations(RelationPost::class)->build());
    }

    #[Test]
    public function it_takes_several_models_at_once(): void
    {
        $this->assertSame('relationUser.relationPost.name', Relation::arrange('name')->relation(RelationUser::class, RelationPost::class)->build());
        $this->assertSame('relationUsers.relationPosts', Relation::arrange()->relations(RelationUser::class, RelationPost::class)->build());
    }

    #[Test]
    public function it_keeps_names_that_are_not_classes(): void
    {
        $this->assertSame('relationPosts.comments', Relation::arrange()->relations(RelationPost::class)->relation('comments')->build());
    }

    #[Test]
    public function it_leaves_out_empty_parts(): void
    {
        $this->assertSame('', Relation::arrange()->build());
        $this->assertSame('name', Relation::arrange('name')->build());
        $this->assertSame('relationUser', Relation::arrange()->relation(RelationUser::class)->build());
        $this->assertSame('name', Relation::arrange('name')->relation()->build());
    }

    #[Test]
    public function it_replaces_nested_arrange_calls(): void
    {
        $this->assertSame(
            Arrange::relations(Arrange::relation('name', RelationUser::class), RelationPost::class),
            Relation::arrange('name')->relations(RelationPost::class)->relation(RelationUser::class)->build()
        );
    }

    #[Test]
    public function it_is_immutable(): void
    {
        $base = Relation::arrange('name');
        $post = $base->relations(RelationPost::class);

        $this->assertSame('relationPosts.relationUser.name', $post->relation(RelationUser::class)->build());
        $this->assertSame('relationPosts.name', $post->build());
        $this->assertSame('name', $base->build());
    }

    #[Test]
    public function it_is_stringable_and_an_expression(): void
    {
        $relation = Relation::arrange('name')->relation(RelationUser::class);

        $this->assertSame('relationUser.name', (string) $relation);
        $this->assertInstanceOf(Expression::class, $relation);
        $this->assertSame('relationUser.name', $relation->getValue(DB::connection()->getQueryGrammar()));
    }
}

final class RelationUser
{
}

final class RelationPost
{
}
