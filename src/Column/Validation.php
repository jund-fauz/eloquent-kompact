<?php

namespace JundFauz\EloquentKompact\Column;

use BadMethodCallException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Stringable;
use Illuminate\Validation\Rules\Unique;
use InvalidArgumentException;
use JundFauz\EloquentKompact\Builder\Collection;
use JundFauz\EloquentKompact\Builder\Str;
use JundFauz\EloquentKompact\Helper\Error\ErrorThrow;
use JundFauz\EloquentKompact\Model\KompactModel;
use JundFauz\EloquentKompact\Model\KompactPivot;
use JundFauz\EloquentKompact\Model\KompactUser;
use JundFauz\EloquentKompact\Regex\Regex;
use function JundFauz\EloquentKompact\Array\in;
use function JundFauz\EloquentKompact\collect;
use function JundFauz\EloquentKompact\is_all;
use function JundFauz\EloquentKompact\is_any;
use function JundFauz\EloquentKompact\is_not;
use function JundFauz\EloquentKompact\is_not_null;
use function JundFauz\EloquentKompact\not;
use function JundFauz\EloquentKompact\not_same_with;
use function JundFauz\EloquentKompact\same_with;
use function JundFauz\EloquentKompact\schema_concat;
use function JundFauz\EloquentKompact\String\str;

/**
 * Immutable fluent builder for Laravel validation rule arrays.
 *
 * @method $this integer()
 * @method $this numeric()
 * @method $this boolean()
 * @method $this required()
 * @method $this array()
 * @method $this min(float|int $num)
 * @method $this max(float|int $num)
 * @method $this digits(int $num)
 * @method $this regex(string|Regex $regex)
 * @method $this digitsBetween(int $from, int $until)
 * @final
 */
class Validation
{
    use ErrorThrow;

    private Collection $payload;

    /**
     * Initialize a validation rule builder with an optional collection of rules.
     *
     * @param Collection<int, string|Unique>|null $payload
     */
    private function __construct(?Collection $payload = null)
    {
        $this->payload = $payload ?? collect();
    }

    /**
     * Create a new empty validation rule builder instance.
     *
     * @return static
     */
    public static function init(): static
    {
        return new static();
    }

    /**
     * Dynamically append supported Laravel validation rules (`integer`, `min`, `digitsBetween`, etc.).
     *
     * @param string $name
     * @param array<int, mixed> $arguments
     * @return static
     * @throws InvalidArgumentException
     * @throws BadMethodCallException
     */
    public function __call(string $name, array $arguments): static
    {
        if (in($name, 'integer,numeric,boolean,required,array')) {
            return $this->add($name);
        } elseif (in($name, 'min,max,digits,regex')) {
            $argument = array_shift($arguments);
            if (count($arguments)) {
                throw new InvalidArgumentException("Argument should be exact one.");
            } elseif (not_same_with($name, 'regex') && is_not('numeric', $argument)) {
                throw new InvalidArgumentException("Argument should be a number.");
            } elseif (same_with($name, 'digits') && is_not('int', $argument)) {
                throw new InvalidArgumentException("Argument should be an integer.");
            } elseif (same_with($name, 'regex') && is_not('string', $argument) && not($argument instanceof Regex)) {
                throw new InvalidArgumentException("Argument should be a string or a Regex instance.");
            }
            return $this->add(schema_concat($name, $argument));
        } elseif (in($name, 'digitsBetween')) {
            if (not_same_with(count($arguments), 2)) {
                throw new InvalidArgumentException("Argument should be exact two.");
            } elseif (is_any('not_int', $arguments)) {
                throw new InvalidArgumentException("All arguments should be an integer.");
            }
            return $this->add(schema_concat(str('snake', $name), implode(',', $arguments)));
        }

        self::methodNotFound($name);
    }

    /**
     * Append a `decimal` validation rule with minimum and/or maximum decimal places.
     *
     * @param int|null $minDecimal
     * @param int|null $maxDecimal
     * @return static
     * @throws BadMethodCallException
     */
    public function decimal(?int $minDecimal = null, ?int $maxDecimal = null): static
    {
        if (is_all('null', $minDecimal, $maxDecimal)) {
            throw new BadMethodCallException("Function should called with at least one parameter.");
        }
        $result = Str::of('decimal:', true);
        if (is_not_null($minDecimal)) {
            $result->concat($minDecimal);
        }
        if (is_all('not_null', $minDecimal, $maxDecimal)) {
            $result->concat(',');
        }
        if (is_not_null($maxDecimal)) {
            $result->concat($maxDecimal);
        }
        return $this->add($result);
    }

    /**
     * Append a `unique` validation rule, either plain or scoped to a Kompact model and optional record.
     *
     * @param string|null $columnName
     * @param class-string<KompactModel|KompactUser|KompactPivot>|null $model
     * @param Model|null $record
     * @return $this
     */
    public function unique(?string $columnName = null, ?string $model = null, ?Model $record = null): static
    {
        return $this->add(is_any('null', $columnName, $model) ? 'unique' : $model::uniqueRule($columnName, $record));
    }

    /**
     * Return the accumulated validation rules as an array.
     *
     * @return array<int, string|Unique>
     */
    public function payload(): array
    {
        return $this->payload->toArray();
    }

    /**
     * Alias for `payload()` returning the accumulated validation rules array.
     *
     * @return array<int, string|Unique>
     */
    public function build(): array
    {
        return $this->payload();
    }

    /**
     * Return a new `Validation` instance with the given rule appended.
     *
     * @param string|Str|Stringable|Unique $payload
     * @return static
     */
    private function add(string|Str|Stringable|Unique $payload): static
    {
        $newInstance = new static(collect($this->payload));
        $newInstance->payload->push($payload instanceof Unique ? $payload : ((string)$payload));
        return $newInstance;
    }
}