<?php
namespace App\Models\HRSI\Lookups;

use Illuminate\Database\Eloquent\Model;

class CivilStatus extends Model
{
    protected $table = 'civil_statuses';
    public $timestamps = false;
    protected $fillable = ['code','name'];
}
