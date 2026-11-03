<?php

namespace JundFauz\EloquentKompact\Model;

use Illuminate\Foundation\Auth\User;
use JundFauz\EloquentKompact\Model\Traits\Kompact;

class KompactUser extends User
{
    use Kompact;
}