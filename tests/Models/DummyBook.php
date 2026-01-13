<?php

namespace AyupCreative\EventLog\Tests\Models;

use AyupCreative\EventLog\Features\LogsEvents;
use Illuminate\Database\Eloquent\Model;

class DummyBook extends Model
{
    use LogsEvents;

    protected $table = 'books';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(DummyUser::class, 'user_id');
    }
}
