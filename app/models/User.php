<?php

class User extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'users';
    public function roles()
    {
        return $this->hasMany('Role');
    }

    //md5($salt.md5($password))
}

