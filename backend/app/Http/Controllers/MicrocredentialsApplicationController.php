<?php

namespace App\Http\Controllers;

use App\Mail\MicroApplicationSubmitted;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use App\Http\Requests\Micro\ApplyRequest;

class MicrocredentialsApplicationController extends Controller
{
    public function store(ApplyRequest $request)
    {
        // Already validated via ApplyRequest
        $validated = $request->validated();

        // Create the user account immediately
        $user = User::create([
            'name'          => trim($validated['firstName'] . ' ' . $validated['lastName']),
            'email'         => $validated['email'],
            'mobile_number' => $validated['mobile'],
            'password'      => Hash::make($validated['password']),
        ]);

        // Assign Spatie role
        $user->assignRole('micro_applicant');

        // Optional: send verification email
        // $user->sendEmailVerificationNotification();

        // Notify program officer
        Mail::to(config('micro.officer_email', 'officer@example.com'))
            ->send(new MicroApplicationSubmitted($user, $validated));

        return response()->json([
            'ok'      => true,
            'message' => 'Application received.',
            'email'   => $validated['email'] ?? null,
        ], 201);
    }

    public function status(\Illuminate\Http\Request $request)
    {
        // You’re already protected by Sanctum + permission:micro.view-status (in routes)
        $user = $request->user();

        // Minimal, safe payload for now (you can replace with real query later)
        return response()->json([
            'ok'   => true,
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
            'status' => 'ready',   // replace with real application status when your DB is set
        ], 200);
    }




}
