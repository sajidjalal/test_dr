<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ErrorLogModel extends Model
{
    use SoftDeletes;

    protected $table = 'error_logs';
    protected $guarded = [];
}
