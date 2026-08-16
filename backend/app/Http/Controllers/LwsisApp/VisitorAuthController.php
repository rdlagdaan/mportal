<?php

namespace App\Http\Controllers\LwsisApp;

use App\Http\Controllers\Controller;
use App\Models\LwsisApp\Visitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class VisitorAuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'first_name' => ['required', 'string', 'max:100'],
                'middle_name' => ['nullable', 'string', 'max:100'],
                'last_name' => ['required', 'string', 'max:100'],
                'suffix' => ['nullable', 'string', 'max:20'],

                'email' => [
                    'required',
                    'email',
                    'max:255',
                ],

                'mobile_number' => [
                    'nullable',
                    'string',
                    'max:30',
                ],

                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'confirmed',
                ],

                'birth_date' => [
                    'nullable',
                    'date',
                    'before:today',
                ],

                'address' => [
                    'nullable',
                    'string',
                    'max:1000',
                ],
            ]);

            $email = strtolower(trim($validated['email']));

            if (Visitor::where('email', $email)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => [
                        'email' => [
                            'The email has already been registered.',
                        ],
                    ],
                ], 422);
            }

            $visitor = Visitor::create([
                'first_name' => trim($validated['first_name']),

                'middle_name' => isset($validated['middle_name'])
                    ? trim($validated['middle_name'])
                    : null,

                'last_name' => trim($validated['last_name']),

                'suffix' => isset($validated['suffix'])
                    ? trim($validated['suffix'])
                    : null,

                'email' => $email,

                'mobile_number' =>
                    $validated['mobile_number'] ?? null,

                'password' =>
                    Hash::make($validated['password']),

                'birth_date' =>
                    $validated['birth_date'] ?? null,

                'address' =>
                    $validated['address'] ?? null,

                'account_status' => 'active',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Visitor account created successfully.',
                'visitor' => $this->visitorData($visitor),
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => [
                    'required',
                    'email',
                    'max:255',
                ],

                'password' => [
                    'required',
                    'string',
                ],
            ]);

            $email = strtolower(trim($validated['email']));

            $visitor = Visitor::where('email', $email)->first();

            if (
                !$visitor ||
                !Hash::check(
                    $validated['password'],
                    $visitor->password
                )
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email or password.',
                ], 401);
            }

            if ($visitor->account_status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Your visitor account is not active.',
                    'account_status' => $visitor->account_status,
                ], 403);
            }

            $visitor
                ->tokens()
                ->where('name', 'visitor-mobile')
                ->delete();

            $token = $visitor
                ->createToken(
                    'visitor-mobile',
                    ['visitor']
                )
                ->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login successful.',
                'token_type' => 'Bearer',
                'token' => $token,
                'visitor' => $this->visitorData($visitor),
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function me(Request $request)
    {
        $visitor = $request->user();

        if (!$visitor instanceof Visitor) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        if ($visitor->account_status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Your visitor account is not active.',
                'account_status' => $visitor->account_status,
            ], 403);
        }

        return response()->json([
            'success' => true,
            'visitor' => $this->visitorData($visitor),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $visitor = $request->user();

        if (!$visitor instanceof Visitor) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        if ($visitor->account_status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Your visitor account is not active.',
            ], 403);
        }

        try {
            $validated = $request->validate([
                'first_name' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:100',
                ],

                'middle_name' => [
                    'sometimes',
                    'nullable',
                    'string',
                    'max:100',
                ],

                'last_name' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:100',
                ],

                'suffix' => [
                    'sometimes',
                    'nullable',
                    'string',
                    'max:20',
                ],

                'mobile_number' => [
                    'sometimes',
                    'nullable',
                    'string',
                    'max:30',
                ],

                'birth_date' => [
                    'sometimes',
                    'nullable',
                    'date',
                    'before:today',
                ],

                'address' => [
                    'sometimes',
                    'nullable',
                    'string',
                    'max:1000',
                ],
            ]);

            if (array_key_exists('first_name', $validated)) {
                $visitor->first_name =
                    trim($validated['first_name']);
            }

            if (array_key_exists('middle_name', $validated)) {
                $visitor->middle_name =
                    $validated['middle_name'] !== null
                        ? trim($validated['middle_name'])
                        : null;
            }

            if (array_key_exists('last_name', $validated)) {
                $visitor->last_name =
                    trim($validated['last_name']);
            }

            if (array_key_exists('suffix', $validated)) {
                $visitor->suffix =
                    $validated['suffix'] !== null
                        ? trim($validated['suffix'])
                        : null;
            }

            if (array_key_exists('mobile_number', $validated)) {
                $visitor->mobile_number =
                    $validated['mobile_number'] !== null
                        ? trim($validated['mobile_number'])
                        : null;
            }

            if (array_key_exists('birth_date', $validated)) {
                $visitor->birth_date =
                    $validated['birth_date'];
            }

            if (array_key_exists('address', $validated)) {
                $visitor->address =
                    $validated['address'] !== null
                        ? trim($validated['address'])
                        : null;
            }

            $visitor->save();
            $visitor->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Visitor profile updated successfully.',
                'visitor' => $this->visitorData($visitor),
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function logout(Request $request)
    {
        $visitor = $request->user();

        if (!$visitor instanceof Visitor) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        $currentToken = $visitor->currentAccessToken();

        if ($currentToken) {
            $currentToken->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logout successful.',
        ]);
    }

    private function visitorData(Visitor $visitor): array
    {
        return [
            'uuid' => $visitor->uuid,
            'first_name' => $visitor->first_name,
            'middle_name' => $visitor->middle_name,
            'last_name' => $visitor->last_name,
            'suffix' => $visitor->suffix,
            'email' => $visitor->email,
            'mobile_number' => $visitor->mobile_number,

            'birth_date' => $visitor->birth_date
                ? $visitor->birth_date->format('Y-m-d')
                : null,

            'address' => $visitor->address,
            'profile_photo' => $visitor->profile_photo,
            'identity_type' => $visitor->identity_type,
            'identity_reference' => $visitor->identity_reference,
            'account_status' => $visitor->account_status,
            'email_verified_at' => $visitor->email_verified_at,
            'mobile_verified_at' => $visitor->mobile_verified_at,
            'created_at' => $visitor->created_at,
            'updated_at' => $visitor->updated_at,
        ];
    }
}