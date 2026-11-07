<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Database\Schema\ForeignKeyDefinition;
use JundFauz\EloquentKompact\Column\Config;
use JundFauz\EloquentKompact\Column\Payload;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ConfigTest extends TestCase
{
    #[Test]
    public function it_applies_a_modifier_name(): void
    {
        $column = Config::applies(self::column('title'), 'nullable');

        $this->assertTrue($column->nullable);
    }

    #[Test]
    public function it_applies_a_list_of_modifiers(): void
    {
        $column = Config::applies(self::column('title'), ['nullable', 'unique']);

        $this->assertTrue($column->nullable);
        $this->assertTrue($column->unique);
    }

    #[Test]
    public function it_applies_modifiers_with_arguments(): void
    {
        $column = Config::applies(self::column('title'), ['unique', 'comment' => 'Shown in the list', 'default' => ['x']]);

        $this->assertTrue($column->unique);
        $this->assertSame('Shown in the list', $column->comment);
        $this->assertSame('x', $column->default);
    }

    #[Test]
    public function it_returns_the_same_column(): void
    {
        $column = self::column('title');

        $this->assertSame($column, Config::applies($column, 'nullable'));
    }

    #[Test]
    public function it_applies_to_a_foreign_key_definition(): void
    {
        $column = Config::applies(new ForeignKeyDefinition(['name' => 'user_id']), ['nullable', 'comment' => 'Owner']);

        $this->assertTrue($column->nullable);
        $this->assertSame('Owner', $column->comment);
    }

    #[Test]
    public function it_applies_all_to_every_column(): void
    {
        [$title, $body] = $columns = [self::column('title'), self::column('body')];

        Config::apply($columns, Payload::allNullable);

        $this->assertTrue($title->nullable);
        $this->assertTrue($body->nullable);
    }

    #[Test]
    public function it_applies_by_column_name(): void
    {
        [$title, $body] = $columns = [self::column('title'), self::column('body')];

        Config::apply($columns, ['title' => 'unique']);

        $this->assertTrue($title->unique);
        $this->assertNull($body->unique);
    }

    #[Test]
    public function it_applies_by_regex(): void
    {
        [$user, $post, $title] = $columns = [self::column('user_id'), self::column('post_id'), self::column('title')];

        Config::apply($columns, ['/_id$/' => ['index']]);

        $this->assertTrue($user->index);
        $this->assertTrue($post->index);
        $this->assertNull($title->index);
    }

    #[Test]
    public function it_applies_rest_to_the_columns_no_name_or_regex_matched(): void
    {
        [$title, $user, $body] = $columns = [self::column('title'), self::column('user_id'), self::column('body')];

        Config::apply($columns, ['title' => 'unique', '/_id$/' => 'index', 'rest' => 'nullable']);

        $this->assertNull($title->nullable);
        $this->assertNull($user->nullable);
        $this->assertTrue($body->nullable);
    }

    #[Test]
    public function it_applies_all_together_with_the_other_keys(): void
    {
        [$title, $body] = $columns = [self::column('title'), self::column('body')];

        Config::apply($columns, ['all' => ['comment' => 'Any'], 'title' => 'unique', 'rest' => 'nullable']);

        $this->assertSame('Any', $title->comment);
        $this->assertSame('Any', $body->comment);
        $this->assertTrue($title->unique);
        $this->assertNull($title->nullable);
        $this->assertTrue($body->nullable);
    }

    #[Test]
    public function it_ignores_a_key_that_is_no_column_name_or_regex(): void
    {
        $title = self::column('title');

        Config::apply([$title], ['missing' => 'unique']);

        $this->assertNull($title->unique);
    }

    #[Test]
    public function it_builds_a_payload(): void
    {
        $this->assertSame(
            ['title' => 'unique', '/_id$/' => 'index', 'rest' => 'nullable'],
            Payload::build(['title' => 'unique'], ['/_id$/' => 'index'], ['rest' => 'nullable'])
        );
        $this->assertSame(['title' => 'nullable'], Payload::build(['title' => 'unique'], ['title' => 'nullable']));
        $this->assertSame(['all' => 'nullable'], Payload::allNullable);
    }

    private static function column(string $name): ColumnDefinition
    {
        return new ColumnDefinition(['name' => $name]);
    }
}
