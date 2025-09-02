<?php
namespace App\Http\Controllers\mdo;

use App\Http\Controllers\Controller;

class PingController extends Controller
{
    public function vitals()        { return response()->json(['ok'=>true,'feature'=>'vitals']); }
    public function consults()      { return response()->json(['ok'=>true,'feature'=>'consultations']); }
    public function rx()            { return response()->json(['ok'=>true,'feature'=>'prescriptions']); }
    public function certificates()  { return response()->json(['ok'=>true,'feature'=>'certificates']); }
    public function referrals()     { return response()->json(['ok'=>true,'feature'=>'referrals']); }
}
