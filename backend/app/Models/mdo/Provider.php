<?php
namespace App\Models\mdo;

use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    protected $table = 'mdo.providers';
    public $timestamps = true;

    protected $fillable = [
        'company_id','users_employee_id','name','role',
        'license_no','active','created_by','updated_by'
    ];
}
