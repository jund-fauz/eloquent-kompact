<?php

namespace JundFauz\EloquentKompact\Helper;

use Illuminate\Support\Arr as LaravelArr;

class Arr
{
    public static function pull(array &$data, string ...$keys): mixed
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = LaravelArr::pull($data, $key);
        }

        return count($values) === 1 ? $values[$keys[0]] : $values;
    }

    public static function isKeysDontExist(array $array, string ...$keys): ?bool
    {
        return array_reduce($keys, fn(bool $acc, string $key) => $acc && ! array_key_exists($key, $array), true);
    }

    public static function arrange(array $array): string
    {
        return join('', $array);
    }
}
