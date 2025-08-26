<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Semester extends Model
{
    protected $fillable = ['code','name','sequence'];
    public $timestamps = false;
}
