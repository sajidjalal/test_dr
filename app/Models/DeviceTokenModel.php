<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeviceTokenModel extends Model
{
    use SoftDeletes;

    protected $table = 'device_tokens';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
