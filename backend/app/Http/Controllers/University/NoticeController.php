<?php

namespace App\Http\Controllers\University;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\Models\Notice;

class NoticeController extends Controller
{
    public function index(Request $request)
    {
        $q        = $request->string('q')->toString();
        $per      = (int) $request->input('per', 10);
        $order    = (string) $request->input('order', 'due_at');
        $dir      = strtolower($request->input('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $company  = $request->integer('company_id') ?? optional($request->user())->company_id;

        $orderBy = Arr::get(Notice::ORDER_MAP, $order, 'due_at');

        $query = Notice::query()
            ->company($company)
            ->published()
            ->search($q);

        // Postgres NULLS LAST so undated items don't jump ahead
        $query->orderByRaw($orderBy.' '.$dir.' NULLS LAST')
              ->orderBy('id', 'desc');

        $results = $query->paginate($per)->appends($request->query());

        $payload = $results->through(function ($n) {
            return [
                'id'                    => $n->id,
                'company_id'            => $n->company_id,
                'notice_code'           => $n->notice_code,
                'title'                 => $n->title,
                'details'               => $n->details,
                'category'              => $n->category,
                'audience'              => $n->audience,
                'target_student_number' => $n->target_student_number,
                'start_date'            => optional($n->start_date)->toDateString(),
                'end_date'              => optional($n->end_date)->toDateString(),
                'venue'                 => $n->venue,
                'start_time'            => $n->start_time ? substr((string)$n->start_time, 0, 5) : null,
                'end_time'              => $n->end_time ? substr((string)$n->end_time, 0, 5) : null,
                'due_at'                => optional($n->due_at)->toAtomString(),
                'grace_until'           => optional($n->grace_until)->toAtomString(),
                'importance'            => (int) $n->importance,
                'status'                => $n->status,
                'is_published'          => (bool) $n->is_published,
                'links'                 => $n->links ?? [],
                'attachments'           => $n->attachments ?? [],
                'metadata'              => $n->metadata ?? [],
                'created_at'            => optional($n->created_at)->toAtomString(),
                'updated_at'            => optional($n->updated_at)->toAtomString(),
            ];
        });

        return response()->json([
            'ok'   => true,
            'data' => $payload, // paginator with transformed items
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
