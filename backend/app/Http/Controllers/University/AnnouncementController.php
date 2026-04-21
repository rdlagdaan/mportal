<?php

namespace App\Http\Controllers\University;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\Models\Announcement; // ✅ correct namespace for the model

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $q        = $request->string('q')->toString();
        $per      = (int) $request->input('per', 10);
        $order    = $request->input('order', 'start_date');
        $dir      = strtolower($request->input('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $company  = $request->integer('company_id') ?? optional($request->user())->company_id;

        // Safe order-by map (from the model)
        $orderBy = Arr::get(Announcement::ORDER_MAP, $order, 'start_date');

        $results = Announcement::query()
            ->company($company)
            ->published()
            ->search($q)
            ->orderBy($orderBy, $dir)
            ->orderBy('id', 'desc')
            ->paginate($per)
            ->appends($request->query());

        $payload = $results->through(function ($a) {
            return [
                'id'                 => $a->id,
                'company_id'         => $a->company_id,
                'announcement_code'  => $a->announcement_code,
                'title'              => $a->title,
                'details'            => $a->details,
                'start_date'         => optional($a->start_date)->toDateString(),
                'end_date'           => optional($a->end_date)->toDateString(),
                'venue'              => $a->venue,
                // TIME columns are strings now → show HH:MM safely (no ->format())
                'start_time'         => $a->start_time ? substr((string)$a->start_time, 0, 5) : null,
                'end_time'           => $a->end_time ? substr((string)$a->end_time, 0, 5) : null,
                'deadline_at'        => optional($a->deadline_at)->toAtomString(),
                'audience'           => $a->audience,
                'importance'         => (int) $a->importance,
                'status'             => $a->status,
                'is_published'       => (bool) $a->is_published,
                'links'              => $a->links ?? [],
                'attachments'        => $a->attachments ?? [],
                'metadata'           => $a->metadata ?? [],
                'created_at'         => optional($a->created_at)->toAtomString(),
                'updated_at'         => optional($a->updated_at)->toAtomString(),
            ];
        });

        return response()->json([
            'ok'   => true,
            'data' => $payload,
            'meta' => [
                'total'        => $results->total(),
                'per_page'     => $results->perPage(),
                'current_page' => $results->currentPage(),
                'last_page'    => $results->lastPage(),
                'order'        => $orderBy,
                'dir'          => $dir,
            ],
        ]);
    }
}
