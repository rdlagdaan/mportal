<?php
namespace App\Models\HRSI\Lookups;

use Illuminate\Database\Eloquent\Model;

class GovIdType extends Model
{
    protected $table = 'government_id_types';
    public $timestamps = false;
    protected $fillable = ['code','name'];
}
