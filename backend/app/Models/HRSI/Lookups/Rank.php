<?php
namespace App\Models\HRSI\Lookups;

use Illuminate\Database\Eloquent\Model;

class Rank extends Model
{
    protected $table = 'ranks';
    public $timestamps = false;
    protected $fillable = ['code','name'];
}
