<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserAccessController extends Controller
{
    // GET /app/api/admin/access/search?q=foo
    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $query = User::query()->select('id','name','email')->orderBy('id','asc');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('email', 'ILIKE', "%{$q}%")
                  ->orWhere('name', 'ILIKE', "%{$q}%");
            });
        }

        $users = $query->limit(20)->get();

        // decorate with app flags
        $apps = DB::table('apps')->select('id','code')->orderBy('id')->get();
        $rows = $users->map(function ($u) use ($apps) {
            $access = DB::table('user_app_access')
                ->where('user_id', $u->id)
                ->pluck('is_enabled', 'app_id');

            $flags = [];
            foreach ($apps as $app) {
                $flags[$app->code] = (bool) ($access[$app->id] ?? false);
            }

            return [
                'id'    => $u->id,
                'name'  => $u->name,
                'email' => $u->email,
                'apps'  => $flags,
            ];
        });

        return response()->json(['ok' => true, 'results' => $rows]);
    }

    // PATCH /app/api/admin/access/{id}  JSON: {app, is_enabled}
    public function updateById(int $id, Request $request)
    {
        $data = $request->validate([
            'app'        => ['required', Rule::in(['LRWSIS','OPENU','MICRO'])],
            'is_enabled' => ['required', 'boolean'],
        ]);

        $user = User::findOrFail($id);

        $appId = DB::table('apps')->where('code', $data['app'])->value('id');
        if (!$appId) {
            return response()->json(['error' => 'App not found: '.$data['app']], 422);
        }

        DB::table('user_app_access')->updateOrInsert(
            ['user_id' => $user->id, 'app_id' => $appId],
            [
                'is_enabled' => (bool) $data['is_enabled'],
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return response()->json([
            'ok'         => true,
            'user_id'    => $user->id,
            'app'        => $data['app'],
            'app_id'     => (int) $appId,
            'is_enabled' => (bool) $data['is_enabled'],
        ]);
    }

    // TEMP: GET /api/admin/access/toggle-id/{id}?app=OPENU&is_enabled=1
    public function toggleById(Request $request, int $id)
    {
        $data = $request->validate([
            'app'        => ['required', Rule::in(['LRWSIS','OPENU','MICRO'])],
            'is_enabled' => ['required', 'boolean'],
        ]);

        $user = User::findOrFail($id);
        $appId = DB::table('apps')->where('code', $data['app'])->value('id');

        DB::table('user_app_access')->updateOrInsert(
            ['user_id' => $user->id, 'app_id' => $appId],
            [
                'is_enabled' => (bool) $data['is_enabled'],
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return response()->json([
            'ok'         => true,
            'user_id'    => $user->id,
            'app'        => $data['app'],
            'app_id'     => (int) $appId,
            'is_enabled' => (bool) $data['is_enabled'],
        ]);
    }
}
