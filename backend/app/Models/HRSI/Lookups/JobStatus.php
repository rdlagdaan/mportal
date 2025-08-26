<?php
namespace App\Models\HRSI\Lookups;

use Illuminate\Database\Eloquent\Model;

class JobStatus extends Model
{
    protected $table = 'job_statuses';
    public $timestamps = false;
    protected $fillable = ['code','name'];
}
