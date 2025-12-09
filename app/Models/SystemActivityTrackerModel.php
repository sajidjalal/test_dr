<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SystemActivityTrackerModel extends Model
{
    use SoftDeletes;
    protected $table = 'system_activity_tracker';
    protected $guarded = [];
}
