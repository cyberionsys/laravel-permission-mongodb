<?php

namespace Cyberion\Mongodb\Permission\Test;

use Cyberion\Mongodb\Permission\Traits\HasRoles;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Jenssegers\Mongodb\Eloquent\Model;

class Admin extends Model implements AuthorizableContract, AuthenticatableContract {
    use HasRoles, Authorizable, Authenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['email'];
    public $timestamps = false;
    protected $collection = 'admins';
}
