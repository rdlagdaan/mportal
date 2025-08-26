<?php
namespace App\Models\HRSI\Lookups;

use Illuminate\Database\Eloquent\Model;

class EducationLevel extends Model
{
    protected $table = 'education_levels';
    public $timestamps = false;
    protected $fillable = ['code','name'];
}
