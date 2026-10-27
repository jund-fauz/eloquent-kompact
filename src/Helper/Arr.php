<?php

namespace JundFauz\EloquentKompact\Helper;

class Arr extends \Illuminate\Support\Arr
{
    public static function pullMany(array &$data, string ...$keys): mixed
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = self::pull($data, $key);
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
