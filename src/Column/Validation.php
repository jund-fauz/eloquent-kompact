<?php

namespace JundFauz\EloquentKompact\Column;

use BadMethodCallException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Stringable;
use Illuminate\Validation\Rules\Unique;
use JundFauz\EloquentKompact\Builder\Collection;
use JundFauz\EloquentKompact\Builder\Str;
use JundFauz\EloquentKompact\Helper\Error\ErrorThrow;
use JundFauz\EloquentKompact\Model\KompactModel;
use JundFauz\EloquentKompact\Model\KompactPivot;
use JundFauz\EloquentKompact\Model\KompactUser;
use JundFauz\EloquentKompact\Regex\Regex;
use function JundFauz\EloquentKompact\collect;
use function JundFauz\EloquentKompact\is_all_not_null;
use function JundFauz\EloquentKompact\is_all_null;
use function JundFauz\EloquentKompact\is_not_null;
use function JundFauz\EloquentKompact\is_one_of_them_null;
use function JundFauz\EloquentKompact\not_in;

/**
 * @method $this integer()
 * @method $this numeric()
 * @method $this boolean()
 * @method $this required()
 */
class Validation
{
    use ErrorThrow;

    private Collection $payload;

    private function __construct(?Collection $payload = null)
    {
        $this->payload = $payload ?? collect();
    }

    public static function init(): static
    {
        return new static();
    }

    public function __call(string $name, array $arguments)
    {
        if (not_in($name, Str::of('integer,numeric,boolean,required')->explode(','))) {
            self::methodNotFound($name);
        }

        return $this->add($name);
    }

    public function decimal(?int $minDecimal = null, ?int $maxDecimal = null): static
    {
        if (is_all_null($minDecimal, $maxDecimal)) {
            throw new BadMethodCallException("Function should called with at least one parameter.");
        }
        $result = Str::of('decimal:', true);
        if (is_not_null($minDecimal)) {
            $result->concat($minDecimal);
        }
        if (is_all_not_null($minDecimal, $maxDecimal)) {
            $result->concat(',');
        }
        if (is_not_null($maxDecimal)) {
            $result->concat($maxDecimal);
        }
        return $this->add($result);
    }

    public function min(float|int $num): static
    {
        return $this->add("min:$num");
    }

    public function max(float|int $num): static
    {
        return $this->add("max:$num");
    }

    public function regex(string|Regex $regex): static
    {
        return $this->add("regex:$regex");
    }

    /**
     * @param string|null $columnName
     * @param class-string<KompactModel|KompactUser|KompactPivot>|null $model
     * @param Model|null $record
     * @return $this
     */
    public function unique(?string $columnName = null, ?string $model = null, ?Model $record = null): static
    {
        return $this->add(is_one_of_them_null($columnName, $model) ? 'unique' : $model::uniqueRule($columnName, $record));
    }

    public function payload(): array
    {
        return $this->payload->toArray();
    }

    public function build(): array
    {
        return $this->payload();
    }

    private function add(string|Str|Stringable|Unique $payload): static
    {
        $newInstance = new static(collect($this->payload));
        $newInstance->payload->push($payload instanceof Unique ? $payload : ((string)$payload));
        return $newInstance;
    }
}