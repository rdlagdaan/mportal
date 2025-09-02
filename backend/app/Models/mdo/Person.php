<?php
namespace App\Models\mdo;

use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    protected $table = 'mdo.persons';
    public $timestamps = true;

    protected $fillable = [
        'company_id','source_type','source_id','full_name','sex','date_of_birth',
        'created_by','updated_by'
    ];
}
