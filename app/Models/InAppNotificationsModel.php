<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InAppNotificationsModel extends Model
{
    use SoftDeletes;
    protected $table = "in_app_notifications";
    protected $guarded = [];
}
