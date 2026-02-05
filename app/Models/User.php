<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
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
        'is_user_customer',
        'user_register_type',
        'history_enabled',
        'usage_notified_at',
    ];

    protected $with = ['organization'];
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
        'password'          => 'hashed',
        'services'          => 'array',
        'usage_notified_at' => 'datetime',
    ];

    public function organization()
    {
        return $this->hasOne(Organization::class, 'id', 'org_id')?->with('instructions');
    }
    public function services()
    {
        return $this->belongsToMany(Service::class, 'services');
    }

    public function organizationalUsers()
    {
        return $this->hasMany(OrganizationalUser::class, 'customer_id');
    }

    public function totalOrganizationalUsers()
    {
        return "hey hey";
        // return $this->organizationalUsers()->count();
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

    public function clonedataprocesses()
    {
        return $this->hasMany(CloneDataProcess::class, 'user_id');
    }

    public function werthenbachs()
    {
        return $this->hasMany(Werthenbach::class, 'user_id');
    }

    public function scherens()
    {
        return $this->hasMany(Scheren::class, 'user_id');
    }

    public function sennheisers()
    {
        return $this->hasMany(Sennheiser::class, 'user_id');
    }
    public function verbunds()
    {
        return $this->hasMany(Verbund::class, 'user_id');
    }

    public function demodataprocesses()
    {
        return $this->hasMany(DemoDataProcess::class, 'user_id');
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

    public function allNormalUsers()
    {
        return $users = OrganizationalUser::where('user_id', Auth::id())->first();
    }
}
