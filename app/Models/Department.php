<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use SoftDeletes;

    public $timestamps = false;

    public function users() {
        return $this->hasMany(User::class);
    }
}
