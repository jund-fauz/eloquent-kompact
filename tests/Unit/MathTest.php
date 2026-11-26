<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use BadMethodCallException;
use DivisionByZeroError;
use InvalidArgumentException;
use JundFauz\EloquentKompact\Math\Math;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class MathTest extends TestCase
{
    #[Test]
    public function it_calculates_with_the_default_precision(): void
    {
        $this->assertSame('2.700', Math::plus('1.5', '1.2'));
        $this->assertSame('0.300', Math::plus(0.1, 0.2));
        $this->assertSame('3.000', Math::plus(1, 2));
        $this->assertSame('-0.750', Math::minus(1.5, 2.25));
        $this->assertSame('2469.000', Math::multiply(1234.5, 2));
        $this->assertSame('0.400', Math::multiply('0.0004', '1000'));
        $this->assertSame('3.333', Math::divide('10', '3'));
        $this->assertSame('16666.666', Math::divide('10', '0.0006'));
        $this->assertSame('2500.000', Math::divide('1', '0.0004'));
    }

    #[Test]
    public function it_calculates_with_the_precision_of_a_child_class(): void
    {
        $this->assertSame('2.70', MathTestTwo::plus('1.5', '1.2'));
        $this->assertSame('3.75', MathTestTwo::minus('5', '1.25'));
        $this->assertSame('100.50', MathTestTwo::multiply('1.005', '100'));
        $this->assertSame('2.55', MathTestTwo::plus('1.555', '1'));
        $this->assertSame('0.33333', MathTestFive::divide(1, 3));
        $this->assertSame('0.0000001500', MathTestTen::plus(1.5e-7, 0));
        $this->assertSame('3', MathTestZero::plus('1.9', '1.9'));
    }

    #[Test]
    public function it_keeps_the_precision_of_the_parent_class_apart(): void
    {
        MathTestTwo::plus(1, 1);

        $this->assertSame('2.000', Math::plus(1, 1));
    }

    #[Test]
    public function it_uses_the_precision_of_the_config(): void
    {
        config(['eloquent-kompact.decimal.default-precision' => 1]);

        $this->assertSame('2.2', Math::plus('1.25', '1'));
        $this->assertSame('2.25', MathTestTwo::plus('1.25', '1'));
    }

    #[Test]
    public function it_reads_floats_exponents_and_padded_numbers(): void
    {
        $this->assertSame('100000000000000000001.000', Math::plus(1e20, 1));
        $this->assertSame('1001.000', Math::plus('1e3', '1'));
        $this->assertSame('1001.000', Math::plus('1E3', '1'));
        $this->assertSame('-150.000', Math::plus('-1.5e2', '0'));
        $this->assertSame('5.000', Math::plus('.5e1', '0'));
        $this->assertSame('3.000', Math::plus(' 2 ', '1'));
        $this->assertTrue(Math::higherThan('1E5', '1'));
        $this->assertTrue(Math::higherThan(1e20, 1));
    }

    #[Test]
    public function it_compares_numbers(): void
    {
        $this->assertTrue(Math::higherThan(2, 1));
        $this->assertTrue(Math::higherThan(1.5, 1));
        $this->assertTrue(Math::higherThan('2.50', '2.00'));
        $this->assertTrue(Math::higherThan('10', '9'));
        $this->assertFalse(Math::higherThan(1, 1));
        $this->assertFalse(Math::higherThan(0.2, 0.9));

        $this->assertTrue(Math::lowerThan(1, 2));
        $this->assertTrue(Math::lowerThan('9', '10'));
        $this->assertTrue(Math::lowerThan(-1, '0.5'));
        $this->assertFalse(Math::lowerThan(1, 1));
        $this->assertFalse(Math::lowerThan('2.50', 2));

        $this->assertTrue(Math::equalTo('2.50', 2.5));
        $this->assertTrue(Math::equalTo(1.9996, '1.9996'));
        $this->assertTrue(Math::equalTo(0.1 + 0.2, 0.3));
        $this->assertFalse(Math::equalTo('1.5', '1.2'));
    }

    #[Test]
    public function it_compares_numbers_or_equal(): void
    {
        $this->assertTrue(Math::higherThanOrEqualTo(2, 1));
        $this->assertTrue(Math::higherThanOrEqualTo(1, 1));
        $this->assertTrue(Math::higherThanOrEqualTo('2.50', 2.5));
        $this->assertFalse(Math::higherThanOrEqualTo(0.2, 0.9));

        $this->assertTrue(Math::lowerThanOrEqualTo(1, 2));
        $this->assertTrue(Math::lowerThanOrEqualTo(1, 1));
        $this->assertTrue(Math::lowerThanOrEqualTo('2.50', 2.5));
        $this->assertFalse(Math::lowerThanOrEqualTo(0.9, 0.2));
    }

    #[Test]
    public function it_compares_numbers_with_the_precision(): void
    {
        $this->assertTrue(Math::equalTo('1.2344', '1.2345'));
        $this->assertFalse(MathTestFour::equalTo('1.2344', '1.2345'));
        $this->assertFalse(Math::higherThan('0.0005', '0'));
        $this->assertTrue(MathTestFour::higherThan('0.0005', '0'));
        $this->assertFalse(Math::lowerThan('1.2344', '1.2345'));
        $this->assertTrue(MathTestFour::lowerThan('1.2344', '1.2345'));
    }

    #[Test]
    public function it_sums_numbers(): void
    {
        $this->assertSame('6.000', Math::sum(1, '2', 3.0));
        $this->assertSame('1.000', Math::sum('1'));
        $this->assertSame('0', Math::sum());
        $this->assertSame('-0.750', Math::sum(1.5, '-2.25'));
        $this->assertSame('1001.000', Math::sum('1e3', ' 1 '));
        $this->assertSame('0.000', Math::sum('0.0001', '0.0002'));
    }

    #[Test]
    public function it_sums_numbers_before_cutting_off_the_decimals(): void
    {
        $this->assertSame('0.001', Math::sum('0.0005', '0.0005'));
        $this->assertSame('0.002', Math::sum('0.0005', '0.0005', '0.0005', '0.0005'));
        $this->assertSame('5.999', Math::sum('1.9999', '1.9999', '1.9999'));
        $this->assertSame(Math::plus('1.9999', '1.9999'), Math::sum('1.9999', '1.9999'));
    }

    #[Test]
    public function it_sums_numbers_with_the_precision(): void
    {
        $this->assertSame('6.00', MathTestTwo::sum('1.555', '1.555', '2.89'));
        $this->assertSame('5', MathTestZero::sum('1.9', '1.9', '1.9'));
        $this->assertSame('0.0000001500', MathTestTen::sum(1.5e-7));

        config(['eloquent-kompact.decimal.default-precision' => 1]);

        $this->assertSame('3.7', Math::sum('1.25', '1.25', '1.25'));
    }

    #[Test]
    public function it_rejects_a_non_number_to_sum(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Math::sum('1', 'abc');
    }

    #[Test]
    public function it_rejects_a_negative_precision_to_sum(): void
    {
        $this->expectException(InvalidArgumentException::class);

        MathTestNegative::sum(1, 2);
    }

    #[Test]
    public function it_flattens_nested_arrays_in_batch_methods(): void
    {
        $this->assertSame('6.000', Math::sum([1, [2]], 3));
        $this->assertSame('5.000', Math::diff([10, 2], [3]));
        $this->assertSame('9.000', Math::product([2, 3], [1.5]));
        $this->assertSame('4.000', Math::quotient([20, 2], [2.5]));
        $this->assertSame('0', Math::sum([[]]));
    }

    #[Test]
    public function it_rejects_an_array_in_two_argument_methods(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Math::plus([1], 2);
    }

    #[Test]
    public function it_calculates_diff_of_numbers(): void
    {
        $this->assertSame('6.250', Math::diff(10, '2.5', 1.25));
        $this->assertSame('1.999', Math::diff('1.9999'));
        $this->assertSame('0', Math::diff());
        $this->assertSame('999.000', Math::diff('1e3', ' 1 '));
        $this->assertSame('3.000', Math::diff('5', '1.9994', '0.0004'));
        $this->assertSame(Math::minus('1.9999', '0.5555'), Math::diff('1.9999', '0.5555'));
        $this->assertSame('1.35', MathTestTwo::diff('5', '2.105', '1.545'));
    }

    #[Test]
    public function it_calculates_product_of_numbers_before_cutting_off_the_decimals(): void
    {
        $this->assertSame('9.000', Math::product(2, '3', 1.5));
        $this->assertSame('1.999', Math::product('1.9999'));
        $this->assertSame('0', Math::product());
        $this->assertSame('9.000', Math::product('-1.5', '2', '-3'));
        $this->assertSame('2.500', Math::product('0.005', '0.5', '1000'));
        $this->assertSame('39.996', Math::product('1.9999', '1.9999', '10'));
        $this->assertSame(Math::multiply('1.9999', '1.9999'), Math::product('1.9999', '1.9999'));
        $this->assertSame('2.50', MathTestTwo::product('0.005', '0.5', '1000'));
        $this->assertSame('0', MathTestZero::product('1.9', '0.5'));
    }

    #[Test]
    public function it_calculates_quotient_of_numbers_before_cutting_off_the_decimals(): void
    {
        $this->assertSame('4.000', Math::quotient(20, '2', 2.5));
        $this->assertSame('3.333', Math::quotient('10', '3'));
        $this->assertSame('1.999', Math::quotient('1.9999'));
        $this->assertSame('0.000', Math::quotient(0));
        $this->assertSame('0', Math::quotient());
        $this->assertSame('3.333', Math::quotient('1', '3', '0.1'));
        $this->assertSame('4.000', Math::quotient('1', '0.5', '0.5'));
        $this->assertSame(Math::divide('10', '3'), Math::quotient('10', '3'));
        $this->assertSame('3.33', MathTestTwo::quotient('1', '3', '0.1'));
    }

    #[Test]
    public function it_fails_on_a_division_by_zero_in_quotient(): void
    {
        $this->expectException(DivisionByZeroError::class);

        Math::quotient(10, 2, '0.00');
    }

    #[Test]
    public function it_rejects_a_non_number(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Math::plus('abc', '1');
    }

    #[Test]
    public function it_rejects_a_value_that_is_no_float_int_or_string(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Math::higherThan(null, 1);
    }

    #[Test]
    public function it_rejects_one_argument(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Math::plus(1);
    }

    #[Test]
    public function it_rejects_three_arguments(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Math::plus(1, 2, 3);
    }

    #[Test]
    public function it_rejects_a_negative_precision(): void
    {
        $this->expectException(InvalidArgumentException::class);

        MathTestNegative::plus(1, 2);
    }

    #[Test]
    public function it_fails_on_a_division_by_zero(): void
    {
        $this->expectException(DivisionByZeroError::class);

        Math::divide(1, 0);
    }

    #[Test]
    public function it_fails_on_an_unknown_method(): void
    {
        $this->expectException(BadMethodCallException::class);

        Math::modulo(5, 2);
    }
}

class MathTestZero extends Math
{
    protected static ?int $precision = 0;
}

class MathTestTwo extends Math
{
    protected static ?int $precision = 2;
}

class MathTestFour extends Math
{
    protected static ?int $precision = 4;
}

class MathTestFive extends Math
{
    protected static ?int $precision = 5;
}

class MathTestTen extends Math
{
    protected static ?int $precision = 10;
}

class MathTestNegative extends Math
{
    protected static ?int $precision = -1;
}
