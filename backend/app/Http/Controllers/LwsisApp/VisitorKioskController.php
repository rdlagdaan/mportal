<?php

namespace App\Http\Controllers\LwsisApp;

use App\Http\Controllers\Controller;
use App\Models\LwsisApp\Visitor;
use App\Models\LwsisApp\VisitorAccessLog;
use App\Models\LwsisApp\VisitorAccessPass;
use App\Models\LwsisApp\VisitorVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VisitorKioskController extends Controller
{
    /**
     * Scan visitor QR code.
     *
     * Flow:
     *
     * QR token
     *   -> SHA-256
     *   -> find access pass
     *   -> validate pass/date/account
     *   -> duplicate scan protection
     *   -> determine ENTRY or EXIT
     *   -> save access log
     */
    public function scan(Request $request)
    {
        try {
            $validated = $request->validate([
                'qr_token' => [
                    'required',
                    'string',
                    'min:32',
                    'max:255',
                ],
            ]);

            $qrToken = trim($validated['qr_token']);

            /*
             * Mobile receives the raw token.
             * PostgreSQL only stores its SHA-256 hash.
             */
            $tokenHash = hash(
                'sha256',
                $qrToken
            );

            /*
             * Find the access pass together with its visit
             * and visitor.
             */
            $accessPass = VisitorAccessPass::where(
                'token_hash',
                $tokenHash
            )->first();

            /*
             * Unknown/old/regenerated QR.
             *
             * We cannot attach this denied scan to a visitor
             * because the supplied token does not identify
             * any current pass.
             */
            if (!$accessPass) {
                return response()->json([
                    'success' => false,
                    'granted' => false,
                    'message' => 'Invalid visitor QR code.',
                    'reason' => 'invalid_qr',
                ], 404);
            }

            $visit = VisitorVisit::find(
                $accessPass->visitor_visit_id
            );

            if (!$visit) {
                return response()->json([
                    'success' => false,
                    'granted' => false,
                    'message' => 'Visitor visit record not found.',
                    'reason' => 'visit_not_found',
                ], 404);
            }

            $visitor = Visitor::find(
                $visit->visitor_id
            );

            if (!$visitor) {
                return response()->json([
                    'success' => false,
                    'granted' => false,
                    'message' => 'Visitor record not found.',
                    'reason' => 'visitor_not_found',
                ], 404);
            }

            /*
             * Visitor account must remain active.
             */
            if ($visitor->account_status !== 'active') {
                $this->createDeniedLog(
                    $visitor,
                    $visit,
                    $accessPass,
                    'Visitor account is not active.'
                );

                return response()->json([
                    'success' => false,
                    'granted' => false,
                    'message' => 'Visitor account is not active.',
                    'reason' => 'visitor_inactive',
                ], 403);
            }

            /*
             * Visit must still be active.
             */
            if ($visit->status !== 'active') {
                $this->createDeniedLog(
                    $visitor,
                    $visit,
                    $accessPass,
                    'Visitor pass is not active.'
                );

                return response()->json([
                    'success' => false,
                    'granted' => false,
                    'message' => 'Visitor pass is not active.',
                    'reason' => 'visit_inactive',
                ], 403);
            }

            /*
             * Access pass itself must be active.
             */
            if ($accessPass->status !== 'active') {
                $this->createDeniedLog(
                    $visitor,
                    $visit,
                    $accessPass,
                    'QR access pass is not active.'
                );

                return response()->json([
                    'success' => false,
                    'granted' => false,
                    'message' => 'QR access pass is not active.',
                    'reason' => 'pass_inactive',
                ], 403);
            }

            $now = now();

            /*
             * Full-day validity check.
             */
            if (
                $now->lt($accessPass->valid_from) ||
                $now->gt($accessPass->valid_until)
            ) {
                $this->createDeniedLog(
                    $visitor,
                    $visit,
                    $accessPass,
                    'QR access pass is outside its valid date.'
                );

                return response()->json([
                    'success' => false,
                    'granted' => false,
                    'message' => 'QR code is not valid at this time.',
                    'reason' => 'outside_validity',
                ], 403);
            }

            /*
             * Process ENTRY / EXIT atomically.
             */
            $result = DB::transaction(
                function () use (
                    $visitor,
                    $visit,
                    $accessPass
                ) {
                    /*
                     * Lock the access pass so two simultaneous
                     * scans of the same QR cannot both create
                     * access events.
                     */
                    $lockedPass = VisitorAccessPass::where(
                        'id',
                        $accessPass->id
                    )
                        ->lockForUpdate()
                        ->first();

                    if (!$lockedPass) {
                        return [
                            'type' => 'error',
                            'message' => 'Access pass not found.',
                        ];
                    }

                    /*
                     * Get last successful scan only.
                     *
                     * Denied scans never determine whether
                     * the next event is ENTRY or EXIT.
                     */
                    $lastGrantedLog = VisitorAccessLog::where(
                        'visitor_access_pass_id',
                        $lockedPass->id
                    )
                        ->where(
                            'result',
                            'granted'
                        )
                        ->orderByDesc('scanned_at')
                        ->orderByDesc('id')
                        ->first();

                    /*
                     * Duplicate scan protection.
                     *
                     * If the last successful scan happened
                     * less than 10 seconds ago, do not toggle
                     * ENTRY -> EXIT accidentally.
                     */
                    if ($lastGrantedLog) {
                        $secondsSinceLastScan =
                            $lastGrantedLog
                                ->scanned_at
                                ->diffInSeconds(
                                    now(),
                                    false
                                );

                        if (
                            $secondsSinceLastScan >= 0 &&
                            $secondsSinceLastScan < 10
                        ) {
                            return [
                                'type' => 'duplicate',

                                'last_event' =>
                                    $lastGrantedLog->event_type,

                                'last_scanned_at' =>
                                    $lastGrantedLog->scanned_at,
                            ];
                        }
                    }

                    /*
                     * Determine next event.
                     *
                     * No previous granted log -> ENTRY
                     * Last ENTRY              -> EXIT
                     * Last EXIT               -> ENTRY
                     */
                    if (!$lastGrantedLog) {
                        $eventType = 'entry';
                    } elseif (
                        $lastGrantedLog->event_type === 'entry'
                    ) {
                        $eventType = 'exit';
                    } else {
                        $eventType = 'entry';
                    }

                    /*
                     * Save successful kiosk scan.
                     */
                    $log = VisitorAccessLog::create([
                        'visitor_id' =>
                            $visitor->id,

                        'visitor_visit_id' =>
                            $visit->id,

                        'visitor_access_pass_id' =>
                            $lockedPass->id,

                        'event_type' =>
                            $eventType,

                        'result' =>
                            'granted',

                        'denial_reason' =>
                            null,

                        'scanned_at' =>
                            now(),

                        'created_at' =>
                            now(),
                    ]);

                    return [
                        'type' => 'granted',
                        'event_type' => $eventType,
                        'log' => $log,
                    ];
                }
            );

            /*
             * Duplicate QR scan.
             *
             * We intentionally DO NOT create another access
             * log because this is scanner debounce, not a
             * real access attempt.
             */
            if ($result['type'] === 'duplicate') {
                return response()->json([
                    'success' => true,
                    'granted' => true,
                    'duplicate' => true,

                    'message' =>
                        'QR code was already scanned moments ago.',

                    'event_type' =>
                        $result['last_event'],

                    'last_scanned_at' =>
                        $result['last_scanned_at'],

                    'visitor' =>
                        $this->visitorData($visitor),

                    'visit' =>
                        $this->visitData($visit),
                ], 200);
            }

            if ($result['type'] === 'error') {
                return response()->json([
                    'success' => false,
                    'granted' => false,
                    'message' => $result['message'],
                ], 404);
            }

            $eventType =
                $result['event_type'];

            return response()->json([
                'success' => true,
                'granted' => true,
                'duplicate' => false,

                'message' =>
                    $eventType === 'entry'
                        ? 'Visitor entry granted.'
                        : 'Visitor exit recorded.',

                'event_type' =>
                    $eventType,

                'scanned_at' =>
                    $result['log']->scanned_at,

                'visitor' =>
                    $this->visitorData($visitor),

                'visit' =>
                    $this->visitData($visit),

                'access_pass' => [
                    'uuid' =>
                        $accessPass->uuid,

                    'status' =>
                        $accessPass->status,

                    'valid_from' =>
                        $accessPass->valid_from,

                    'valid_until' =>
                        $accessPass->valid_until,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'granted' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Save a denied access attempt when we already know
     * which visitor/pass was scanned.
     *
     * Denied logs never affect ENTRY/EXIT alternation.
     */
    private function createDeniedLog(
        Visitor $visitor,
        VisitorVisit $visit,
        VisitorAccessPass $accessPass,
        string $reason
    ): void {
        /*
         * event_type is NOT NULL and only allows
         * entry/exit.
         *
         * Determine what the attempted event would have
         * been based on the last granted scan.
         */
        $lastGrantedLog = VisitorAccessLog::where(
            'visitor_access_pass_id',
            $accessPass->id
        )
            ->where('result', 'granted')
            ->orderByDesc('scanned_at')
            ->orderByDesc('id')
            ->first();

        if (!$lastGrantedLog) {
            $eventType = 'entry';
        } elseif (
            $lastGrantedLog->event_type === 'entry'
        ) {
            $eventType = 'exit';
        } else {
            $eventType = 'entry';
        }

        VisitorAccessLog::create([
            'visitor_id' =>
                $visitor->id,

            'visitor_visit_id' =>
                $visit->id,

            'visitor_access_pass_id' =>
                $accessPass->id,

            'event_type' =>
                $eventType,

            'result' =>
                'denied',

            'denial_reason' =>
                $reason,

            'scanned_at' =>
                now(),

            'created_at' =>
                now(),
        ]);
    }

    private function visitorData(
        Visitor $visitor
    ): array {
        return [
            'uuid' =>
                $visitor->uuid,

            'first_name' =>
                $visitor->first_name,

            'middle_name' =>
                $visitor->middle_name,

            'last_name' =>
                $visitor->last_name,

            'suffix' =>
                $visitor->suffix,

            'full_name' =>
                trim(
                    implode(
                        ' ',
                        array_filter([
                            $visitor->first_name,
                            $visitor->middle_name,
                            $visitor->last_name,
                            $visitor->suffix,
                        ])
                    )
                ),

            'profile_photo' =>
                $visitor->profile_photo,
        ];
    }

    private function visitData(
        VisitorVisit $visit
    ): array {
        return [
            'uuid' =>
                $visit->uuid,

            'purpose' =>
                $visit->purpose,

            'destination' =>
                $visit->destination,

            'person_to_visit' =>
                $visit->person_to_visit,

            'visit_date' =>
                $visit->visit_date
                    ? $visit->visit_date->format('Y-m-d')
                    : null,

            'status' =>
                $visit->status,
        ];
    }
}