<?php

namespace JundFauz\EloquentKompact\Model;

use Illuminate\Foundation\Auth\User;
use JundFauz\EloquentKompact\Model\Traits\Kompact;

abstract class KompactUser extends User
{
    use Kompact;

    protected static string $collectionClass = Collection::class;
    protected static string $builder = Builder::class;
}