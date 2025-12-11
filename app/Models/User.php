<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'role_id',
        'branch_id',
        'gender_master_id',
        'reporting_id',        // Allow mass assignment for role_id
        'first_name',      // Add all necessary fields here
        'middle_name',
        'last_name',
        'full_name',
        'mobile_number',
        'email',
        'created_by',
        'updated_by',
        'user_code',
        'user_code_number',
        'is_backend',
        'status',
        'pincode_master_id',
        'city',
        'state',
        'date_of_birth',
        'date_of_joining',
        'address',
        'created_at',
        'updated_at',
        'run_seeder_access',
        'password'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ---------- EMAIL ----------
    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = customEncrypt($value);
    }

    public function getEmailAttribute($value)
    {
        return customDecrypt($value);
    }

    // ---------- MOBILE ----------
    public function setMobileNumberAttribute($value)
    {
        $this->attributes['mobile_number'] = customEncrypt($value);
    }

    public function getMobileNumberAttribute($value)
    {
        return customDecrypt($value);
    }

    public function participants()
    {
        return $this->hasMany(ChatParticipant::class, 'user_id', 'id');
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
