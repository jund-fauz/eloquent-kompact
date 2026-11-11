<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use BadMethodCallException;
use Illuminate\Database\Eloquent\Model;
use JundFauz\EloquentKompact\Column\Validation;
use JundFauz\EloquentKompact\Regex\Regex;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ValidationBuilderTest extends TestCase
{
    #[Test]
    public function it_builds_the_rules_in_order(): void
    {
        $this->assertSame(
            ['numeric', 'min:0.5', 'max:10', 'decimal:0,3', 'integer', 'boolean', 'regex:/^a$/'],
            Validation::init()->numeric()->min(0.5)->max(10)->decimal(0, 3)->integer()->boolean()->regex(Regex::create('a')->exact())->build(),
        );
    }

    #[Test]
    public function it_starts_empty(): void
    {
        $this->assertSame([], Validation::init()->build());
        $this->assertSame([], Validation::init()->payload());
    }

    #[Test]
    public function it_builds_the_decimal_rule(): void
    {
        $this->assertSame(['decimal:2'], Validation::init()->decimal(2)->build());
        $this->assertSame(['decimal:0,3'], Validation::init()->decimal(0, 3)->build());
        $this->assertSame(['decimal:3'], Validation::init()->decimal(maxDecimal: 3)->build());
    }

    #[Test]
    public function it_needs_a_parameter_for_the_decimal_rule(): void
    {
        $this->expectException(BadMethodCallException::class);

        Validation::init()->decimal();
    }

    #[Test]
    public function it_leaves_the_original_unchanged(): void
    {
        $base = Validation::init()->numeric();

        $this->assertSame(['numeric', 'min:1'], $base->min(1)->build());
        $this->assertSame(['numeric', 'max:2'], $base->max(2)->build());
        $this->assertSame(['numeric'], $base->build());
    }

    #[Test]
    public function it_returns_the_subclass(): void
    {
        $this->assertInstanceOf(ValidationBuilderSubclass::class, ValidationBuilderSubclass::init()->integer());
    }

    #[Test]
    public function it_is_accepted_by_define_rules_and_default_rules(): void
    {
        $this->assertSame(['integer', 'min:1'], ValidationBuilderModel::rules()['name']);
        $this->assertSame(['required'], ValidationBuilderModel::rules()['other']);
    }
}

class ValidationBuilderSubclass extends Validation
{
}

class ValidationBuilderModel extends \JundFauz\EloquentKompact\Model\KompactModel
{
    protected $table = 'missing_table';

    public function getFillable(): array
    {
        return ['name', 'other'];
    }

    protected function defineRules(?Model $record = null): array
    {
        return ['name' => Validation::init()->integer()->min(1)];
    }

    protected static function defaultRules(string $column, ?Model $record = null): array
    {
        return $column === 'other' ? ['required'] : [];
    }
}
