<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    
    protected $commands = [
        \App\Console\Commands\GrantAppAccess::class,
    ];    
    
    protected function schedule(Schedule $schedule): void
    {
        $schedule->call(function () {
            $rows = \DB::table('leave_approval_steps as s')
                ->join('leave_requests as lr','lr.id','=','s.leave_request_id')
                ->where('s.status','pending')
                ->select('s.approver_employee_id', \DB::raw('COUNT(*) as c'))
                ->groupBy('s.approver_employee_id')
                ->get();

            /** @var \App\Services\HRSI\LeaveManagement\Notifier $notifier */
            $notifier = app(\App\Services\HRSI\LeaveManagement\Notifier::class);

            foreach ($rows as $r) {
                if (!$r->approver_employee_id) continue;
                $notifier->notifyEmployeeId(
                    (int)$r->approver_employee_id,
                    new \App\Notifications\HRSI\LeaveManagement\PendingApprovalReminder((int)$r->approver_employee_id, (int)$r->c)
                );
            }
        })->dailyAt('08:00');
    }
}
