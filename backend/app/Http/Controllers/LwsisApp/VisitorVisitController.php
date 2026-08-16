<?php

namespace App\Http\Controllers\LwsisApp;

use App\Http\Controllers\Controller;
use App\Models\LwsisApp\Visitor;
use App\Models\LwsisApp\VisitorAccessPass;
use App\Models\LwsisApp\VisitorVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VisitorVisitController extends Controller
{
    /**
     * Get all visitor passes/history.
     */
    public function index(Request $request)
    {
        $visitor = $request->user();

        if (!$visitor instanceof Visitor) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        $visits = VisitorVisit::where(
            'visitor_id',
            $visitor->id
        )
            ->with('accessPasses')
            ->orderByDesc('visit_date')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,

            'visits' => $visits
                ->map(
                    fn (VisitorVisit $visit) =>
                        $this->visitData($visit)
                )
                ->values(),
        ]);
    }

    /**
     * Generate today's visitor QR access pass.
     *
     * Final rules:
     *
     * - Visitor only provides the purpose.
     * - Visit date comes from the Laravel server.
     * - No admin/staff approval.
     * - Visit becomes active immediately.
     * - One visitor + one date = one visit.
     * - One visit = one access pass.
     * - QR is valid only for the current server date.
     * - Same QR can be used for multiple ENTRY/EXIT scans.
     * - Generating again on the same day rotates the QR token.
     * - Previous QR becomes invalid after regeneration.
     */
    public function store(Request $request)
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
            /*
             * Mobile app only sends:
             *
             * {
             *     "purpose": "..."
             * }
             */
            $validated = $request->validate([
                'purpose' => [
                    'required',
                    'string',
                    'max:1000',
                ],
            ]);

            /*
             * IMPORTANT:
             *
             * Visit date is determined by the Laravel server,
             * NOT by the visitor's phone.
             */
            $visitDate = now()->startOfDay();

            /*
             * QR is valid for the entire current server date.
             *
             * Example:
             *
             * valid_from  = 2026-08-16 00:00:00
             * valid_until = 2026-08-16 23:59:59
             */
            $validFrom = $visitDate
                ->copy()
                ->startOfDay();

            $validUntil = $visitDate
                ->copy()
                ->endOfDay();

            /*
             * Generate a cryptographically secure QR token.
             *
             * Raw token:
             *     returned ONLY to the mobile app.
             *
             * Database:
             *     stores only SHA-256 hash.
             */
            $qrToken = bin2hex(
                random_bytes(32)
            );

            $tokenHash = hash(
                'sha256',
                $qrToken
            );

            $result = DB::transaction(
                function () use (
                    $visitor,
                    $validated,
                    $visitDate,
                    $validFrom,
                    $validUntil,
                    $tokenHash
                ) {
                    /*
                     * Find today's existing visit.
                     *
                     * Database should also enforce:
                     *
                     * UNIQUE(visitor_id, visit_date)
                     */
                    $visit = VisitorVisit::where(
                        'visitor_id',
                        $visitor->id
                    )
                        ->where(
                            'visit_date',
                            $visitDate->format('Y-m-d')
                        )
                        ->lockForUpdate()
                        ->first();

                    $existingVisit =
                        $visit !== null;

                    /*
                     * -------------------------------------------------
                     * CREATE TODAY'S VISIT
                     * -------------------------------------------------
                     */
                    if (!$visit) {
                        $visit = VisitorVisit::create([
                            'visitor_id' =>
                                $visitor->id,

                            'purpose' =>
                                trim(
                                    $validated['purpose']
                                ),

                            /*
                             * No longer collected
                             * from the visitor.
                             */
                            'destination' =>
                                null,

                            'person_to_visit' =>
                                null,

                            /*
                             * Server-generated date.
                             */
                            'visit_date' =>
                                $visitDate->format('Y-m-d'),

                            /*
                             * No longer used.
                             */
                            'expected_time_from' =>
                                null,

                            'expected_time_to' =>
                                null,

                            /*
                             * No approval workflow.
                             */
                            'status' =>
                                'active',

                            'approved_by' =>
                                null,

                            'approved_at' =>
                                null,

                            'rejected_at' =>
                                null,

                            'rejection_reason' =>
                                null,

                            'cancelled_at' =>
                                null,

                            'completed_at' =>
                                null,
                        ]);
                    }

                    /*
                     * -------------------------------------------------
                     * UPDATE EXISTING TODAY'S VISIT
                     * -------------------------------------------------
                     *
                     * Same visitor generating another QR
                     * during the same day.
                     */
                    else {
                        $visit->purpose =
                            trim(
                                $validated['purpose']
                            );

                        /*
                         * These fields are no longer
                         * part of visitor QR generation.
                         */
                        $visit->destination =
                            null;

                        $visit->person_to_visit =
                            null;

                        /*
                         * Reactivate today's visit.
                         */
                        $visit->status =
                            'active';

                        $visit->approved_by =
                            null;

                        $visit->approved_at =
                            null;

                        $visit->rejected_at =
                            null;

                        $visit->rejection_reason =
                            null;

                        $visit->cancelled_at =
                            null;

                        $visit->completed_at =
                            null;

                        $visit->save();
                    }

                    /*
                     * -------------------------------------------------
                     * FIND TODAY'S ACCESS PASS
                     * -------------------------------------------------
                     *
                     * Database should enforce:
                     *
                     * UNIQUE(visitor_visit_id)
                     */
                    $accessPass =
                        VisitorAccessPass::where(
                            'visitor_visit_id',
                            $visit->id
                        )
                            ->lockForUpdate()
                            ->first();

                    $existingPass =
                        $accessPass !== null;

                    /*
                     * -------------------------------------------------
                     * CREATE ACCESS PASS
                     * -------------------------------------------------
                     */
                    if (!$accessPass) {
                        $accessPass =
                            VisitorAccessPass::create([
                                'visitor_visit_id' =>
                                    $visit->id,

                                'token_hash' =>
                                    $tokenHash,

                                'valid_from' =>
                                    $validFrom,

                                'valid_until' =>
                                    $validUntil,

                                'status' =>
                                    'active',

                                /*
                                 * NULL means unlimited
                                 * ENTRY / EXIT scans
                                 * during today's validity.
                                 */
                                'max_entries' =>
                                    null,

                                'issued_at' =>
                                    now(),

                                'revoked_at' =>
                                    null,
                            ]);
                    }

                    /*
                     * -------------------------------------------------
                     * REGENERATE ACCESS PASS
                     * -------------------------------------------------
                     *
                     * Keep the same access pass database row,
                     * but replace its secret token hash.
                     *
                     * This automatically invalidates the
                     * previous QR code.
                     */
                    else {
                        $accessPass->token_hash =
                            $tokenHash;

                        $accessPass->valid_from =
                            $validFrom;

                        $accessPass->valid_until =
                            $validUntil;

                        $accessPass->status =
                            'active';

                        $accessPass->max_entries =
                            null;

                        $accessPass->issued_at =
                            now();

                        $accessPass->revoked_at =
                            null;

                        $accessPass->save();
                    }

                    return [
                        'visit' =>
                            $visit,

                        'access_pass' =>
                            $accessPass,

                        'existing_visit' =>
                            $existingVisit,

                        'existing_pass' =>
                            $existingPass,
                    ];
                }
            );

            $visit =
                $result['visit'];

            $accessPass =
                $result['access_pass'];

            /*
             * Existing pass means this was
             * a same-day QR regeneration.
             */
            $rotated =
                $result['existing_pass'];

            /*
             * -------------------------------------------------
             * RESPONSE
             * -------------------------------------------------
             *
             * qr_token is returned here because the
             * mobile app needs the raw value to render
             * the QR code.
             *
             * PostgreSQL never stores this raw token.
             */
            return response()->json([
                'success' =>
                    true,

                'message' =>
                    $rotated
                        ? 'Visitor QR regenerated successfully.'
                        : 'Visitor QR generated successfully.',

                'regenerated' =>
                    $rotated,

                'visit' => [
                    'uuid' =>
                        $visit->uuid,

                    'purpose' =>
                        $visit->purpose,

                    'visit_date' =>
                        $visit->visit_date
                            ? $visit->visit_date->format('Y-m-d')
                            : null,

                    'status' =>
                        $visit->status,
                ],

                'access_pass' => [
                    'uuid' =>
                        $accessPass->uuid,

                    'status' =>
                        $accessPass->status,

                    'valid_date' =>
                        $visit->visit_date
                            ? $visit->visit_date->format('Y-m-d')
                            : null,

                    'valid_from' =>
                        $accessPass->valid_from,

                    'valid_until' =>
                        $accessPass->valid_until,

                    'max_entries' =>
                        $accessPass->max_entries,

                    'issued_at' =>
                        $accessPass->issued_at,

                    /*
                     * Secret credential rendered
                     * as QR by the mobile app.
                     */
                    'qr_token' =>
                        $qrToken,
                ],
            ], $rotated ? 200 : 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' =>
                    false,

                'message' =>
                    'Validation failed.',

                'errors' =>
                    $e->errors(),
            ], 422);
        }
    }

    /**
     * Show one visitor visit/pass.
     *
     * IMPORTANT:
     *
     * Raw QR token is intentionally NOT returned here.
     */
    public function show(
        Request $request,
        string $uuid
    ) {
        $visitor = $request->user();

        if (!$visitor instanceof Visitor) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        $visit = VisitorVisit::where(
            'uuid',
            $uuid
        )
            ->where(
                'visitor_id',
                $visitor->id
            )
            ->with('accessPasses')
            ->first();

        if (!$visit) {
            return response()->json([
                'success' => false,
                'message' => 'Visitor pass not found.',
            ], 404);
        }

        return response()->json([
            'success' =>
                true,

            'visit' =>
                $this->visitData($visit),
        ]);
    }

    /**
     * Standard visitor pass/history response.
     *
     * Raw QR token is intentionally NOT included.
     */
    private function visitData(
        VisitorVisit $visit
    ): array {
        /*
         * Current model relationship is HasMany,
         * although the database guarantees
         * one access pass per visit.
         */
        $accessPass =
            $visit->relationLoaded('accessPasses')
                ? $visit->accessPasses->first()
                : $visit->accessPasses()->first();

        return [
            'uuid' =>
                $visit->uuid,

            'purpose' =>
                $visit->purpose,

            'visit_date' =>
                $visit->visit_date
                    ? $visit->visit_date->format('Y-m-d')
                    : null,

            'status' =>
                $visit->status,

            'access_pass' =>
                $accessPass
                    ? [
                        'uuid' =>
                            $accessPass->uuid,

                        'status' =>
                            $accessPass->status,

                        'valid_from' =>
                            $accessPass->valid_from,

                        'valid_until' =>
                            $accessPass->valid_until,

                        'max_entries' =>
                            $accessPass->max_entries,

                        'issued_at' =>
                            $accessPass->issued_at,
                    ]
                    : null,

            'created_at' =>
                $visit->created_at,

            'updated_at' =>
                $visit->updated_at,
        ];
    }
}