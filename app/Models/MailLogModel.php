<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MailLogModel extends Model
{
    use SoftDeletes;

    protected $table = 'email_log';
    protected $guarded = [];
}
