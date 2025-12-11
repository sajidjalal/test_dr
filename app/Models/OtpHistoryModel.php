<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OtpHistoryModel extends Model
{
    use SoftDeletes;
    protected $table  = "otp_history";
    protected $guarded = [];
}
