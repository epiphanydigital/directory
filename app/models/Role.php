<?php

class Role extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'user_roles';
    public function user()
    {
        return $this->belongsTo('User');
    }
}