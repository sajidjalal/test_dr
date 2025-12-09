<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PushNotificationsModel extends Model
{
    use SoftDeletes;
    protected $table  = "push_notifications";
    protected $guarded = [];
}
