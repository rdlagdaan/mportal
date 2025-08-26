<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class DepreciationScheduleExport implements FromView
{
    public function __construct(
        private View $view
    ) {}

    public function view(): View
    {
        return $this->view;
    }
}
