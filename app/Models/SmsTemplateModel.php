<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmsTemplateModel extends Model
{
    use SoftDeletes;
    protected $table  = "sms_template";
    protected $guarded = [];
}
