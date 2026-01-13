<?php

namespace AyupCreative\EventLog\Tests\Models;

use AyupCreative\EventLog\Features\LogsEvents;
use Illuminate\Foundation\Auth\User as Authenticatable;

class DummyUser extends Authenticatable
{
    use LogsEvents;

    protected $table = 'users';
    protected $guarded = [];

    public function books()
    {
        return $this->hasMany(DummyBook::class, 'user_id');
    }
}
