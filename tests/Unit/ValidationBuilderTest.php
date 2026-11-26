<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use BadMethodCallException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;
use JundFauz\EloquentKompact\Column\Validation;
use JundFauz\EloquentKompact\Regex\Regex;
use InvalidArgumentException;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
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
    public function it_builds_the_digits_rules(): void
    {
        $this->assertSame(['digits:3'], Validation::init()->digits(3)->build());
        $this->assertSame(['digits_between:1,3'], Validation::init()->digitsBetween(1, 3)->build());
        $this->assertSame(['numeric', 'digits:4'], Validation::init()->numeric()->digits(4)->build());
    }

    #[Test]
    public function it_builds_the_regex_rule_from_a_string(): void
    {
        $this->assertSame(['regex:/^a$/'], Validation::init()->regex('/^a$/')->build());
    }

    #[Test]
    public function it_takes_named_arguments(): void
    {
        $this->assertSame(['min:5'], Validation::init()->min(num: 5)->build());
        $this->assertSame(['digits_between:1,3'], Validation::init()->digitsBetween(from: 1, until: 3)->build());
    }

    #[Test]
    public function it_takes_a_numeric_string_for_min_and_max(): void
    {
        $this->assertSame(['min:5', 'max:10.5'], Validation::init()->min('5')->max('10.5')->build());
    }

    /**
     * @return array<string, array{string, array}>
     */
    public static function invalidArguments(): array
    {
        return [
            'min without an argument' => ['min', []],
            'max with two arguments' => ['max', [1, 2]],
            'min with a non-numeric string' => ['min', ['abc']],
            'digits with a float' => ['digits', [3.5]],
            'digits with a numeric string' => ['digits', ['3']],
            'regex with an int' => ['regex', [123]],
            'regex with an array' => ['regex', [['a']]],
            'digitsBetween with one argument' => ['digitsBetween', [1]],
            'digitsBetween with a numeric string' => ['digitsBetween', [1, '3']],
        ];
    }

    #[Test]
    #[DataProvider('invalidArguments')]
    public function it_throws_on_invalid_arguments(string $method, array $arguments): void
    {
        $this->expectException(InvalidArgumentException::class);

        Validation::init()->{$method}(...$arguments);
    }

    #[Test]
    public function it_builds_the_required_rule(): void
    {
        $this->assertSame(['required', 'integer'], Validation::init()->required()->integer()->build());
    }

    #[Test]
    public function it_builds_the_array_rule(): void
    {
        $this->assertSame(['required', 'array'], Validation::init()->required()->array()->build());
    }

    #[Test]
    public function it_throws_on_an_unknown_rule_method(): void
    {
        $this->expectException(BadMethodCallException::class);

        Validation::init()->string();
    }

    #[Test]
    public function it_builds_the_unique_rule_of_a_model(): void
    {
        $rules = Validation::init()->required()->unique('name', ValidationBuilderUniqueModel::class)->build();

        $this->assertSame('required', $rules[0]);
        $this->assertInstanceOf(Unique::class, $rules[1]);
        $this->assertSame('unique:unique_probes,name,NULL,id', (string) $rules[1]);
    }

    #[Test]
    public function it_ignores_the_record_in_the_unique_rule(): void
    {
        $record = new ValidationBuilderUniqueModel();
        $record->id = 5;

        $this->assertSame(
            'unique:unique_probes,name,"5",id',
            (string) Validation::init()->unique('name', ValidationBuilderUniqueModel::class, $record)->build()[0],
        );
    }

    #[Test]
    public function it_builds_the_plain_unique_rule_without_a_column_or_model(): void
    {
        $this->assertSame(['unique'], Validation::init()->unique()->build());
        $this->assertSame(['unique'], Validation::init()->unique('name')->build());
        $this->assertSame(['unique'], Validation::init()->unique(model: ValidationBuilderUniqueModel::class)->build());
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

class ValidationBuilderUniqueModel extends \JundFauz\EloquentKompact\Model\KompactModel
{
    protected $table = 'unique_probes';
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
