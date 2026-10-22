<?php

namespace JundFauz\EloquentKompact\Column;

class Payload
{
    public const array allNullable = ['all' => 'nullable'];

    public static function build(array ...$payloads): array
    {
        return array_merge(...$payloads);
    }
}
