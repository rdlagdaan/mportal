<?php

namespace App\Http\Controllers\LwsisApp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class KioskFaceController extends Controller
{
    /**
     * Check whether the currently authenticated
     * LWSIS employee is enrolled in the kiosk
     * face recognition system.
     */
    public function status(Request $request)
    {
        try {
            // -------------------------------------------------
            // 1. Get authenticated LWSIS user
            // -------------------------------------------------

            $userId = $request->attributes->get('auth_user_id');

            if (!$userId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized',
                ], 401);
            }

            // -------------------------------------------------
            // 2. Resolve LWSIS user -> employee
            // -------------------------------------------------

            $employeeId = DB::table('iam.user_employee_links')
                ->where('user_id', $userId)
                ->value('employee_id');

            if (!$employeeId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No employee record linked to this account.',
                ], 404);
            }

            // -------------------------------------------------
            // 3. Ask kiosk FastAPI for enrollment status
            // -------------------------------------------------

            $response = Http::connectTimeout(3)
                ->timeout(10)
                ->acceptJson()
                ->get(
                    "http://tua-kiosk-backend:8000/api/face/enrollment-status/{$employeeId}"
                );

            // -------------------------------------------------
            // 4. Employee not found by kiosk backend
            // -------------------------------------------------

            if ($response->status() === 404) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Employee was not found in the kiosk system.',
                    'employee_id' => (int) $employeeId,
                ], 404);
            }

            // -------------------------------------------------
            // 5. Unexpected kiosk response
            // -------------------------------------------------

            if (!$response->successful()) {
                Log::error('Kiosk face status request failed.', [
                    'employee_id' => $employeeId,
                    'http_status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Unable to check kiosk enrollment status.',
                ], 502);
            }

            $kiosk = $response->json();

            // -------------------------------------------------
            // 6. Return mobile-friendly response
            // -------------------------------------------------

            return response()->json([
                'status' => 'success',

                'employee_id' => (int) $employeeId,

                'institutional_id' =>
                    $kiosk['institutional_id'] ?? null,

                'face_enrolled' =>
                    (bool) ($kiosk['enrolled'] ?? false),

                'enrollment_status' =>
                    $kiosk['enrollment_status'] ?? 'not_started',

                'samples_completed' =>
                    (int) ($kiosk['samples_completed'] ?? 0),

                'samples_required' =>
                    (int) ($kiosk['samples_required'] ?? 3),

                'enrolled_at' =>
                    $kiosk['enrolled_at'] ?? null,
            ]);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Unable to connect to kiosk backend.', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Kiosk service is currently unavailable.',
            ], 503);

        } catch (\Throwable $e) {
            Log::error('Kiosk face status error.', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Unable to check kiosk enrollment status.',
            ], 500);
        }
    }

    /**
     * Enroll one face sample for the currently
     * authenticated LWSIS employee.
     *
     * The mobile app does NOT provide employee_id.
     * Employee identity is derived from the
     * authenticated LWSIS token.
     */
    public function enroll(Request $request)
    {
        try {
            // -------------------------------------------------
            // 1. Get authenticated LWSIS user
            // -------------------------------------------------

            $userId = $request->attributes->get('auth_user_id');

            if (!$userId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized',
                ], 401);
            }

            // -------------------------------------------------
            // 2. Validate incoming enrollment sample
            // -------------------------------------------------

            $validated = $request->validate([
                'sample_number' => [
                    'required',
                    'integer',
                    'between:1,3',
                ],

                'image' => [
                    'required',
                    'file',
                    'image',
                    'mimes:jpeg,jpg,png,webp',
                    'max:10240',
                ],
            ]);

            // -------------------------------------------------
            // 3. Resolve LWSIS user -> employee
            // -------------------------------------------------

            $employeeId = DB::table('iam.user_employee_links')
                ->where('user_id', $userId)
                ->value('employee_id');

            if (!$employeeId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No employee record linked to this account.',
                ], 404);
            }

            // -------------------------------------------------
            // 4. Get uploaded image
            // -------------------------------------------------

            $image = $request->file('image');

            if (!$image || !$image->isValid()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid image upload.',
                ], 422);
            }

            // -------------------------------------------------
            // 5. Open image stream
            // -------------------------------------------------

            $stream = fopen(
                $image->getRealPath(),
                'r'
            );

            if ($stream === false) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unable to read uploaded image.',
                ], 500);
            }

            try {
                // ---------------------------------------------
                // 6. Forward sample to internal kiosk FastAPI
                // ---------------------------------------------

                $response = Http::connectTimeout(3)
                    ->timeout(60)
                    ->acceptJson()
                    ->attach(
                        'image',
                        $stream,
                        $image->getClientOriginalName(),
                        [
                            'Content-Type' =>
                                $image->getMimeType(),
                        ]
                    )
                    ->post(
                        "http://tua-kiosk-backend:8000/api/face/enroll/{$employeeId}",
                        [
                            'sample_number' =>
                                (string) $validated['sample_number'],
                        ]
                    );

            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            // -------------------------------------------------
            // 7. Decode FastAPI response
            // -------------------------------------------------

            $body = $response->json();

            if (!is_array($body)) {
                Log::error('Invalid kiosk enrollment response.', [
                    'employee_id' => $employeeId,
                    'sample_number' =>
                        $validated['sample_number'],
                    'http_status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid response from kiosk face service.',
                ], 502);
            }

            // -------------------------------------------------
            // 8. Log failed kiosk requests
            // -------------------------------------------------

            if (!$response->successful()) {
                Log::warning('Kiosk face enrollment rejected.', [
                    'employee_id' => $employeeId,
                    'sample_number' =>
                        $validated['sample_number'],
                    'http_status' => $response->status(),
                    'response' => $body,
                ]);
            }

            // -------------------------------------------------
            // 9. Return FastAPI result to mobile
            //
            // Preserve FastAPI HTTP status:
            // 200 success
            // 400 invalid face/image
            // 404 employee not found
            // 409 duplicate/already enrolled
            // etc.
            // -------------------------------------------------

            return response()->json(
                $body,
                $response->status()
            );

        } catch (ValidationException $e) {
            throw $e;

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error(
                'Unable to connect to kiosk backend during enrollment.',
                [
                    'message' => $e->getMessage(),
                ]
            );

            return response()->json([
                'status' => 'error',
                'message' => 'Kiosk service is currently unavailable.',
            ], 503);

        } catch (\Throwable $e) {
            Log::error('Kiosk face enrollment error.', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Unable to process kiosk face enrollment.',
            ], 500);
        }
    }
}