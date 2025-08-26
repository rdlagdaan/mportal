<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AppSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['code' => 'LRWSIS', 'name' => 'LRWSIS',              'session_cookie' => 'lrwsis_session', 'session_path' => '/lrwsis'],
            ['code' => 'OPENU',  'name' => 'TUA Open University', 'session_cookie' => 'open_session',   'session_path' => '/open'],
            ['code' => 'MICRO',  'name' => 'Microcredentials',    'session_cookie' => 'micro_session',  'session_path' => '/app'],
        ];

        foreach ($rows as $r) {
            DB::table('apps')->updateOrInsert(
                ['code' => $r['code']],
                $r + ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
