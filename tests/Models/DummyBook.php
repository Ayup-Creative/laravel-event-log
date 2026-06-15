<?php

namespace AyupCreative\EventLog\Tests\Models;

use AyupCreative\EventLog\Features\LogsEvents;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DummyBook extends Model
{
    use HasUuids;
    use LogsEvents;
    use SoftDeletes;

    protected $table = 'books';

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(DummyUser::class, 'user_id');
    }
}
