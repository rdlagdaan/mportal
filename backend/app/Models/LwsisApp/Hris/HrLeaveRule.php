<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;

class HrLeaveRule extends Model
{
    protected $table = 'hris.hr_leave_rules';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'rule_set_id',
        'rule_key',
        'operator',
        'value_type',
        'value_int',
        'value_decimal',
        'value_bool',
        'value_text',
        'value_date',
        'value_json',
        'is_active',
        'remarks',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'value_int' => 'integer',
        'value_decimal' => 'decimal:2',
        'value_bool' => 'boolean',
        'value_date' => 'date',
        'value_json' => 'array',
        'is_active' => 'boolean',
    ];

    public function ruleSet()
    {
        return $this->belongsTo(HrLeaveRuleSet::class, 'rule_set_id');
    }
}