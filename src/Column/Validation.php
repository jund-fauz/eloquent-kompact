<?php

namespace JundFauz\EloquentKompact\Column;

use BadMethodCallException;
use Illuminate\Support\Stringable;
use JundFauz\EloquentKompact\Builder\Collection;
use JundFauz\EloquentKompact\Builder\Str;
use JundFauz\EloquentKompact\Regex\Regex;
use function JundFauz\EloquentKompact\collect;
use function JundFauz\EloquentKompact\is_all_not_null;
use function JundFauz\EloquentKompact\is_all_null;
use function JundFauz\EloquentKompact\is_not_null;
use function JundFauz\EloquentKompact\is_one_of_them_not_null;

class Validation
{
    private Collection $payload;

    private function __construct(?Collection $payload = null)
    {
        $this->payload = $payload ?? collect();
    }

    public static function init(): static
    {
        return new static();
    }

    public function integer(): static
    {
        return $this->add('integer');
    }

    public function numeric(): static
    {
        return $this->add('numeric');
    }

    public function decimal(?int $minDecimal = null, ?int $maxDecimal = null): static
    {
        if (is_all_null($minDecimal, $maxDecimal)) {
            throw new BadMethodCallException("Function should called with at least one parameter.");
        }
        $result = Str::of('decimal:');
        if (is_not_null($minDecimal)) {
            $result = $result->concat($minDecimal);
        }
        if (is_all_not_null($minDecimal, $maxDecimal)) {
            $result = $result->concat(',');
        }
        if (is_not_null($maxDecimal)) {
            $result = $result->concat($maxDecimal);
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

    public function boolean(): static
    {
        return $this->add('boolean');
    }

    public function payload(): array
    {
        return $this->payload->toArray();
    }

    public function build(): array
    {
        return $this->payload();
    }

    private function add(string|Str|Stringable $payload): static
    {
        $newInstance = new static(collect($this->payload));
        $newInstance->payload->push((string) $payload);
        return $newInstance;
    }
}