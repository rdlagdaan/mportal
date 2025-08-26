<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;

//Broadcast::channel('user.{userId}', fn ($user, $userId) => (int)$user->id === (int)$userId);

Broadcast::channel('company.{companyId}.assets', function ($user, $companyId) {
    return (int) $user->company_id === (int) $companyId;
});


Broadcast::channel('employee.{employeeId}', function ($user, $employeeId) {
    //$empId = optional($user->employee)->id
    //      ?? DB::table('employees')->where('user_id', $user->id)->value('id');

    //return (int) $empId === (int) $employeeId;
    return true;
    
});

Broadcast::channel('approver.{employeeId}', function ($user, $employeeId) {
    //$empId = optional($user->employee)->id
    //      ?? DB::table('employees')->where('user_id', $user->id)->value('id');

    //return (int) $empId === (int) $employeeId;
    return true;
});
