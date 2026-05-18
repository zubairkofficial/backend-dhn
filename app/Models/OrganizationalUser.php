<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Pivot between customer admin, org admin, and member users. Domain tables (`documents`, `*_processes`, …)
 * use `user_id` as `users.id` for whoever created the row — that is unrelated to column names here.
 *
 * - **user_id**: organizational (org-admin) user — the “owner” side of the org’s seat row (`users.id`).
 * - **organizational_id**: when set, the member (normal) user belonging under that org admin (`users.id`).
 * - **customer_id**: optional link to the customer-admin user who provisioned the org (`users.id`).
 *
 * Example: customer admin creates an org admin (row: user_id = org admin, customer_id = customer).
 * Rows for each seat add organizational_id = member’s `users.id` with the same user_id = org admin.
 */
class OrganizationalUser extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'organizational_id', 'customer_id'];

    // Define relationships (if necessary)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // public function organizational()
    // {
    //     return $this->hasMany(OrganizationalUser::class,  'user_id','customer_id')->with('normalUser');
    // }
    // public function normalUser()
    // {
    //     return $this->hasMany(OrganizationalUser::class, 'organizational_id');
    // }
    public function organizational()
    {
        return $this->hasMany(OrganizationalUser::class, 'user_id', 'user_id')->with('normalUser');
    }
    public function normalOrganizational()
    {
        return $this->belongsTo(User::class);
    }

    // Normal users (linked via organizational_id)
    public function normalUser()
    {
        return $this->hasMany(OrganizationalUser::class, 'organizational_id', 'organizational_id');
    }

    public function normalUserToolCount(){
        return $this->normalUser()->count();
    }
}