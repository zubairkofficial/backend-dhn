<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'services',
        'org_id',
        'is_user_organizational',
        'is_user_customer'
    ];

    protected $with =['organization'];
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
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'services' => 'array',
    ];

    public function organization()
    {
        return $this->hasOne(Organization::class, 'id', 'org_id')?->with('instructions');
    }
    public function services()
    {
        return $this->belongsToMany(Service::class, 'services');
    }

    public function organizationUsers(): HasMany
    {
        return $this->hasMany(OrganizationalUser::class, 'organization_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'user_id');
    }

    public function contractSolutions()
    {
        return $this->hasMany(ContractSolutions::class, 'user_id');
    }

    public function dataprocesses()
    {
        return $this->hasMany(DataProcess::class, 'user_id');
    }

    public function freedataprocesses()
    {
        return $this->hasMany(FreeDataProcess::class, 'user_id');
    }

    public function customerUsers()
    {
        return $this->hasMany(OrganizationalUser::class, 'customer_id')->with('organizational');
    }
    public function customerUserWithNullOrganization()
    {
        return $this->hasOne(OrganizationalUser::class, 'customer_id')
            ->whereNull('organizational_id');
    }
}
