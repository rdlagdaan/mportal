<?php
namespace App\Models\mdo;

use Illuminate\Database\Eloquent\Model;

class Queue extends Model
{
    protected $table = 'mdo.queues';
    public $timestamps = false; // has updated_at, but we’ll set it manually when needed

    protected $fillable = [
        'company_id','encounter_id','station','position','status',
        'queued_at','called_at','served_at','updated_at'
    ];
}
