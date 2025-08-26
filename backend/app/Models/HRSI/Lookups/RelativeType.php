<?php
namespace App\Models\HRSI\Lookups;

use Illuminate\Database\Eloquent\Model;

class RelativeType extends Model
{
    protected $table = 'relative_types';
    public $timestamps = false;
    protected $fillable = ['code','name'];
}
