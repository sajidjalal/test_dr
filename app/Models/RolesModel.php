<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class RolesModel extends Model
{
    use SoftDeletes;
    protected $table  = "roles";
    protected $guarded = [];

    const IS_ADMIN = 1;

    public static function getRoleList(): array
    {
        return Cache::remember('role_list', CACHE_TIME, function () {
            return self::pluck('display_name as name', 'id')->toArray();
        });
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->withDefault();
    }
}
