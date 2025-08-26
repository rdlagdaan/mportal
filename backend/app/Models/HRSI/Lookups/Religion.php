<?php
namespace App\Models\HRSI\Lookups;

use Illuminate\Database\Eloquent\Model;

class Religion extends Model
{
    protected $table = 'religions';
    public $timestamps = false;
    protected $fillable = ['code','name'];
}
