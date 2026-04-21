<?php

namespace App\Http\Controllers\Lrwsis;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class MeController extends Controller
{
    public function show(Request $request)
    {

        $u = $request->user()->loadMissing('employee'); // relation: user->employee

        return response()->json([
            'user' => [
                'id'           => $u->id,
                'employee_id'  => optional($u->employee)->id ?? null, // or ->employee_id field name
                'name'         => $u->name ?? null,
                'email'        => $u->email ?? null,
            ],
        ]);


    }
}
