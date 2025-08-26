<?php

namespace App\Http\Controllers\HRSI\LeaveManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaveReportController extends Controller
{
    public function employeeSummary($empId, Request $req)
    {
        $sy = $req->integer('school_year_id');
        return DB::table('leave_balances')
            ->when($sy, fn($q)=>$q->where('school_year_id',$sy))
            ->where('employee_id',$empId)
            ->get();
    }

    public function officeSummary($orgId, Request $req)
    {
        $sy = $req->integer('school_year_id');
        return DB::table('leave_balance_ledger as l')
            ->join('employees as e','e.id','=','l.employee_id')
            ->join('employee_role_assignments as era','era.employee_id','=','e.id')
            ->where('era.org_unit_id',$orgId)
            ->whereRaw('era.valid_during @> CURRENT_DATE')
            ->when($sy, fn($q)=>$q->where('l.school_year_id',$sy))
            ->where('qty_days','<',0)
            ->select('l.leave_type_id', DB::raw('SUM(-qty_days) as used_days'))
            ->groupBy('l.leave_type_id')
            ->get();
    }

    public function orgSummary(Request $req)
    {
        $sy = $req->integer('school_year_id');
        return DB::table('leave_balance_ledger as l')
            ->when($sy, fn($q)=>$q->where('l.school_year_id',$sy))
            ->where('qty_days','<',0)
            ->select('l.leave_type_id', DB::raw('SUM(-qty_days) as used_days'))
            ->groupBy('l.leave_type_id')
            ->get();
    }
}
