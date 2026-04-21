<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ImportUserController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt',
            'kind' => 'required|in:student,employee'
        ]);

        $kind = $request->kind;
        $file = $request->file('file');

        $handle = fopen($file->getRealPath(), 'r');

        if (!$handle) {
            return response()->json(['error' => 'Unable to read file'], 400);
        }

        /* ======================================================
           1️⃣  Detect delimiter (TAB or comma)
        ====================================================== */
        $firstLine = fgets($handle);

        $delimiter = substr_count($firstLine, "\t") > substr_count($firstLine, ",")
            ? "\t"
            : ",";

        rewind($handle);

        /* ======================================================
           2️⃣  Read & normalize header
        ====================================================== */
        $header = fgetcsv($handle, 0, $delimiter);

        $header = array_map(function ($value) {
            $value = preg_replace('/^\xEF\xBB\xBF/', '', $value); // remove BOM
            $value = strtolower(trim($value));
            $value = str_replace(' ', '_', $value);
            return $value;
        }, $header);

        /* ======================================================
           3️⃣  Required columns
        ====================================================== */
        $required = [
            'student_number',
            'last_name',
            'first_name',
            'middle_name',
            'email',
            'mobile_number',
            'password'
        ];

        foreach ($required as $col) {
            if (!in_array($col, $header)) {
                return response()->json([
                    'error' => "Missing column: {$col}"
                ], 400);
            }
        }

        /* ======================================================
           4️⃣  Prepare containers
        ====================================================== */
        $usersInsert = [];
        $identityInsert = [];
        $profilesInsert = [];

        $inserted = 0;
        $skipped = 0;
        $now = Carbon::now();

        DB::beginTransaction();

        try {

            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {

                if (count($row) !== count($header)) {
                    continue;
                }

                $row = array_map(fn($v) => trim($v), $row);
                $data = array_combine($header, $row);

                $studentNumber = $data['student_number'] ?? null;
                $email = strtolower($data['email'] ?? '');

                if (!$studentNumber || !$email) {
                    $skipped++;
                    continue;
                }

                /* ============================================
                   Build full name
                ============================================ */
                $fullName = trim(
                    $data['first_name'] . ' ' .
                    ($data['middle_name'] ? $data['middle_name'] . ' ' : '') .
                    $data['last_name']
                );

                /* ============================================
                   Hash password if not hashed
                ============================================ */
                $password = Str::startsWith($data['password'], '$2y$')
                    ? $data['password']
                    : Hash::make($data['password']);

                /* ============================================
                   USERS TABLE
                ============================================ */
                $usersInsert[] = [
                    'name' => $fullName,
                    'email' => $email,
                    'mobile_number' => $data['mobile_number'],
                    'password' => $password,

                    // Nullable
                    'email_verified_at' => null,
                    'remember_token' => null,

                    // Defaults
                    'micro_enabled' => false,
                    'lrwsis_enabled' => false,
                    'open_enabled' => false,
                    'biometrics_enabled' => false,
                    'company_id' => 0,

                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                /* ============================================
                   PROFILES
                ============================================ */
                if ($kind === 'student') {

                    $profilesInsert[] = [
                        'student_number' => $studentNumber,
                        'last_name' => $data['last_name'],
                        'first_name' => $data['first_name'],
                        'middle_name' => $data['middle_name'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($kind === 'employee') {

                    $profilesInsert[] = [
                        'employee_number' => $studentNumber,
                        'last_name' => $data['last_name'],
                        'first_name' => $data['first_name'],
                        'middle_name' => $data['middle_name'],
                        'active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                $inserted++;
            }

            fclose($handle);

            /* ======================================================
               5️⃣ BULK INSERT USERS (IGNORE DUPLICATES EMAIL)
            ====================================================== */
            foreach (array_chunk($usersInsert, 1000) as $chunk) {
                DB::table('public.users')->insertOrIgnore($chunk);
            }

            /* ======================================================
               6️⃣ Get inserted users (for identity linking)
            ====================================================== */
            $emails = array_column($usersInsert, 'email');

            $users = DB::table('public.users')
                ->whereIn('email', $emails)
                ->get(['id', 'email']);

            $emailMap = [];
            foreach ($users as $u) {
                $emailMap[$u->email] = $u->id;
            }

            /* ======================================================
               7️⃣ Prepare identity insert
            ====================================================== */
            foreach ($usersInsert as $index => $userRow) {

                $email = $userRow['email'];

                if (!isset($emailMap[$email])) {
                    continue;
                }

                $studentNumber = $profilesInsert[$index]['student_number'] ?? null;
                $employeeNumber = $profilesInsert[$index]['employee_number'] ?? null;

                $identityInsert[] = [
                    'user_id' => $emailMap[$email],
                    'kind' => $kind,
                    'student_number' => $kind === 'student' ? $studentNumber : null,
                    'employee_number' => $kind === 'employee' ? $employeeNumber : null,
                    'guest_number' => null,
                ];
            }

            /* ======================================================
               8️⃣ BULK INSERT IDENTITY (IGNORE DUPLICATES)
            ====================================================== */
            foreach (array_chunk($identityInsert, 1000) as $chunk) {
                DB::table('public.user_identity_links')->insertOrIgnore($chunk);
            }

            /* ======================================================
               9️⃣ BULK INSERT PROFILES (IGNORE DUPLICATE NUMBER)
            ====================================================== */
            if ($kind === 'student') {
                foreach (array_chunk($profilesInsert, 1000) as $chunk) {
                    DB::table('mobile.student_profiles')->insertOrIgnore($chunk);
                }
            }

            if ($kind === 'employee') {
                foreach (array_chunk($profilesInsert, 1000) as $chunk) {
                    DB::table('mobile.employee_profiles')->insertOrIgnore($chunk);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Import completed successfully',
                'kind' => $kind,
                'processed_rows' => $inserted,
                'skipped_rows' => $skipped
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}