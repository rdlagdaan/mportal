<?php

namespace App\Http\Controllers\Libraries;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reference\Region;
use App\Models\Reference\Province;
use App\Models\Reference\CityMunicipality;
use App\Models\Reference\Barangay;
use App\Models\Reference\Zipcode;

class AddressController extends Controller
{
    public function regions()
    {
        return Region::query()
            ->select('id','region_code','region_name','territory')
            ->orderBy('region_name')
            ->get();
    }

    public function provinces(Request $r)
    {
        $rid = (int) ($r->query('region_id') ?? 0);

        return Province::query()
            ->select('id','province_name','region_id')
            ->when($rid > 0, fn($q) => $q->where('region_id', $rid))
            ->orderBy('province_name')
            ->get();
    }

    public function cities(Request $r)
    {
        $pid = (int) ($r->query('province_id') ?? 0);

        return CityMunicipality::query()
            ->selectRaw('id as cmid, citymunicipality as name, cmtype, province_id')
            ->when($pid > 0, fn($q) => $q->where('province_id', $pid))
            ->orderBy('citymunicipality')
            ->get();
    }

    public function barangays(Request $r)
    {
        $cmid = (int) ($r->query('cmid') ?? 0);

        return Barangay::query()
            ->selectRaw('id as barangay_id, barangay, cmid')
            ->when($cmid > 0, fn($q) => $q->where('cmid', $cmid))
            ->orderBy('barangay')
            ->get();
    }

    public function zipcodes(Request $r)
    {
        $pid  = (int) ($r->query('province_id') ?? 0);
        $cmid = (int) ($r->query('cmid') ?? 0);
        $bid  = (int) ($r->query('barangay_id') ?? 0);

        return Zipcode::query()
            ->selectRaw('id as zipcode_id, zipcode, province_id, cmid, barangay_id')
            ->when($pid  > 0, fn($q) => $q->where('province_id', $pid))
            ->when($cmid > 0, fn($q) => $q->where('cmid', $cmid))
            ->when($bid  > 0, fn($q) => $q->where('barangay_id', $bid))
            ->orderBy('zipcode')
            ->get();
    }

    public function resolve(Request $r)
    {
        $zid = (int) ($r->query('zipcode_id') ?? 0);

        if ($zid > 0) {
            $zip = Zipcode::find($zid);
            if (!$zip) return response()->json(['error' => 'Zipcode not found'], 404);

            $province_id = $zip->province_id ?? null;
            $cmid        = $zip->cmid ?? null;
            $barangay_id = $zip->barangay_id ?? null;

            $region_id = $province_id
                ? Province::where('id', $province_id)->value('region_id')
                : null;

            return response()->json([
                'region_id'   => $region_id,
                'province_id' => $province_id,
                'cmid'        => $cmid,
                'barangay_id' => $barangay_id,
                'zipcode_id'  => $zid,
            ]);
        }

        $province_id = (int) ($r->query('province_id') ?? 0) ?: null;
        $cmid        = (int) ($r->query('cmid') ?? 0) ?: null;
        $barangay_id = (int) ($r->query('barangay_id') ?? 0) ?: null;

        $region_id = $province_id
            ? Province::where('id', $province_id)->value('region_id')
            : null;

        $zipcode_id = Zipcode::query()
            ->when($province_id, fn($q) => $q->where('province_id', $province_id))
            ->when($cmid,        fn($q) => $q->where('cmid', $cmid))
            ->when($barangay_id, fn($q) => $q->where('barangay_id', $barangay_id))
            ->orderBy('zipcode')
            ->value('id');

        return response()->json(compact('region_id','province_id','cmid','barangay_id','zipcode_id'));
    }
}
