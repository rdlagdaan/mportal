<?php

namespace App\Models\LwsisApp;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/*
|--------------------------------------------------------------------------
| USER EMPLOYEE LINKS
|--------------------------------------------------------------------------
*/
class UserEmployeeLink extends Model
{
    use HasFactory;

    protected $table = 'iam.user_employee_links';

    public $incrementing = false;
    protected $primaryKey = 'user_id';

    protected $fillable = [
        'user_id',
        'employee_id',
        'linked_by'
    ];

    protected $casts = [
        'linked_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
