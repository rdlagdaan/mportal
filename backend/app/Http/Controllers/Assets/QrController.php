<?php
namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\DB;

class QrController extends Controller
{
    // Encodes a deep-link (or payload) you choose. Here we encode a canonical "asset:" payload
    private function payloadForAsset($assetId){
        $asset = DB::table('assets')->where('id',$assetId)->first(['asset_number','description']);
        $num = $asset?->asset_number ?? (string)$assetId;
        return json_encode(['type'=>'asset','id'=>$assetId,'asset_number'=>$num], JSON_UNESCAPED_SLASHES);
    }
    private function payloadForCustody($assetId,$seq){
        $c = DB::table('asset_custody')->where('asset_id',$assetId)->where('seq_id',$seq)->first(['serial_no']);
        return json_encode(['type'=>'asset_unit','asset_id'=>$assetId,'seq_id'=>(int)$seq,'serial'=>$c?->serial_no], JSON_UNESCAPED_SLASHES);
    }

    public function assetSvg($assetId){
        $svg = QrCode::format('svg')->size(256)->margin(0)->generate($this->payloadForAsset($assetId));
        return response($svg, 200)->header('Content-Type','image/svg+xml');
    }

    public function custodySvg($assetId, $seq){
        $svg = QrCode::format('svg')->size(256)->margin(0)->generate($this->payloadForCustody($assetId,$seq));
        return response($svg, 200)->header('Content-Type','image/svg+xml');
    }
}
