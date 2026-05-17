<?php

use Illuminate\Support\Facades\Broadcast;

/**
 * IMPORTANT:
 * - These names DO NOT include the "private-" prefix.
 *   Echo.private('employee.16') → channel_name sent is "private-employee.16"
 *   so you declare it here as "employee.{employeeId}".
 */
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});



Broadcast::channel('company.{companyId}.assets', function ($user, $companyId) {
    return (int)($user->company_id ?? 0) === (int)$companyId;
});

Broadcast::channel('employee.{employeeId}', function ($user, $employeeId) {
    // Use a real check if you have $user->employee_id
    return (int)($user->employee_id ?? 0) === (int)$employeeId;

    // For quick smoke tests, you could temporarily do:
    // return true;
});

Broadcast::channel('approver.{employeeId}', function ($user, $employeeId) {
    return (int)($user->employee_id ?? 0) === (int)$employeeId;

    // Or temporarily:
    // return true;
});




Broadcast::channel('App.Models.User.{id}', fn ($user, $id) => (int)$user->id === (int)$id);

// (Optional) your leave/status channel
Broadcast::channel('leave.status.{id}', fn ($user, $id) => (int)$user->id === (int)$id);

Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});