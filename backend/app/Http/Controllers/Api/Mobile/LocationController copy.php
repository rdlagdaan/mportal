<?php 

namespace App\Http\Controllers\Api\Mobile; 

use App\Http\Controllers\Controller; 
use Illuminate\Http\Request; 
use App\Models\Mobile\UserLocationLog; 
use App\Services\GeofenceService; 

class LocationController extends Controller { 
    
    public function update(Request $request) { 
    // 🔐 Apple-review safe guard 
    if (!auth()->check()) { 
        return response()->json([ 
            'status' => 'ignored', 
            'reason' => 'Unauthenticated background call' 
            ], 200); 
    }
            
        $request->validate([ 
            'latitude' => 'required|numeric', 
            'longitude' => 'required|numeric', 
            'event' => 'required|in:entered,exited', 
            'occurred_at' => 'nullable|integer', 
        ]);
                
            UserLocationLog::create([ 'user_id' => auth()->id(), 
                'latitude' => $request->latitude, 
                'longitude' => $request->longitude, 
                'mocked' => $request->mocked ?? false, 
            ]); 
        
            try { app(GeofenceService::class)->process( 
                    auth()->id(), 
                    $request->latitude, 


                    
                    $request->longitude, 
                    $request->event, 
                    $request->occurred_at
                    ); 
                } catch (\Throwable $e) {   
                    // ❌ NEVER crash background calls 
                    \Log::warning('Geofence skipped', [ 
                        'error' => $e->getMessage() 
                        ]); 
                } 
    return response()->json(['status' => 'ok']); 
}
}
 