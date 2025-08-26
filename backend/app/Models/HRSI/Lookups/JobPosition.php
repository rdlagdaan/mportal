<?php
namespace App\Models\HRSI\Lookups;

use Illuminate\Database\Eloquent\Model;

class JobPosition extends Model
{
    protected $table = 'job_positions';
    public $timestamps = false;
    protected $fillable = ['code','name'];
}
