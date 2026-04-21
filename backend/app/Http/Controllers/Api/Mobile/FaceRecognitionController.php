<?php

namespace App\Http\Controllers\Api\Mobile;

use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Mobile\Users;
use App\Models\Mobile\StudentProfile;
use App\Models\Mobile\EnrollmentHistory;
use App\Models\Mobile\EmployeeProfile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\Mobile\FaceId;
use App\Services\FaceEmbeddingService;
use Illuminate\Support\Facades\Storage;
class FaceRecognitionController extends Controller
{

    public function registerLocal(Request $request, FaceEmbeddingService $faceService)
{
    $request->validate([
        'user_id' => 'required|integer|exists:App\Models\Mobile\Users,id',
        'image'   => 'required|string',
    ]);

    $user = Users::findOrFail($request->user_id);

    // 1) Strip optional data:image/... prefix
    $imageData = preg_replace('#^data:image/\w+;base64,#i', '', $request->image);
    $imageData = str_replace(' ', '+', $imageData);

    // 2) Call Python (InsightFace)
    $res = $faceService->extractEmbedding($imageData);
    \Log::info("FaceService Response", $res);

    if (!($res['success'] ?? false)) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Face embedding failed',
            'debug'   => $res['error'] ?? null,
        ], 422);
    }

    $embedding = $res['embedding'];

    // 3) Save image
    $binary = base64_decode($imageData);
    $path = "faces/user_{$user->id}.jpg";
    \Storage::disk('public')->put($path, $binary);
    $publicPath = "/storage/{$path}";

    // 4) Create/update FaceId
    $faceRecord = $user->faceid ?: new \App\Models\Mobile\FaceId();

    $faceRecord->face_vector     = $embedding;
    $faceRecord->face_image_path = $publicPath;
    $faceRecord->is_enabled      = true;
    $faceRecord->save();

    // 5) Link to user
    $user->faceid_id      = $faceRecord->id;
    $user->faceid_enabled = true;
    $user->save();

    return response()->json([
        'status'  => 'success',
        'message' => 'Local Face ID registered successfully',
    ]);
}

public function faceLogin(Request $request, FaceEmbeddingService $faceService)
{
    try {
        $request->validate([
            'image' => 'required|string',
        ]);

        // 1️⃣ Clean base64 string (remove prefix if present)
        $imageData = preg_replace('#^data:image/\w+;base64,#i', '', $request->image);
        $imageData = str_replace(' ', '+', $imageData);

        // 2️⃣ Ask Python (InsightFace) for embedding
        $res = $faceService->extractEmbedding($imageData);

        if (!($res['success'] ?? false)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Face embedding failed',
                'debug'   => $res['error'] ?? null,
            ], 422);
        }

        // Normalize vector
        $probeEmbedding = $this->normalizeVector($res['embedding']);

        // 3️⃣ Get all users with active Face IDs
        $users = Users::with('faceid')
            ->whereNotNull('faceid_id')
            ->where('faceid_enabled', true)
            ->get()
            ->filter(fn($u) => $u->faceid && $u->faceid->is_enabled);

        if ($users->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No registered faces found.',
            ], 404);
        }

        // 4️⃣ Compare using cosine similarity
        $bestUser = null;
        $bestScore = -INF;

        foreach ($users as $candidate) {
            $stored = $candidate->faceid->face_vector ?? [];
            if (empty($stored)) continue;

            $stored = $this->normalizeVector($stored);
            $score = $this->cosineSimilarity($probeEmbedding, $stored);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestUser = $candidate;
            }
        }

        // 5️⃣ Adjust threshold (InsightFace = 512-dim, usually 0.6–0.7)
        $threshold = 0.7;

        if (!$bestUser || $bestScore < $threshold) {
            return response()->json([
                'status'      => 'error',
                'message'     => 'Face not recognized',
                'confidence'  => round($bestScore, 4),
            ], 401);
        }

        // 6️⃣ Issue token
        $token = $bestUser->createToken('auth_token')->plainTextToken;

        // 7️⃣ Load relations (optional)
        $relations = [];
        if (method_exists($bestUser, 'identityLinks')) $relations[] = 'identityLinks';
        if (method_exists($bestUser, 'roles')) $relations[] = 'roles';
        if (method_exists($bestUser, 'userType')) $relations[] = 'userType';
        if (method_exists($bestUser, 'appAccesses')) $relations[] = 'appAccesses';
        $bestUser->load($relations);

        // 8️⃣ Identify user kind (student/employee)
        $identity = $bestUser->identityLinks?->first();
        $kind = $identity?->kind ?? null;

        $studentProfile = null;
        $enrollmentHistory = [];
        $employeeProfile = null;

        if ($kind === 'student') {
            $studentNumber = $identity->student_number;
            $studentProfile = StudentProfile::where('student_number', $studentNumber)->first();
            $enrollmentHistory = EnrollmentHistory::where('student_number', $studentNumber)
                ->orderBy('created_at', 'desc')
                ->get();
        } elseif ($kind === 'employee') {
            $employeeNumber = $identity->employee_number;
            $employeeProfile = EmployeeProfile::where('employee_number', $employeeNumber)->first();
        }

        // 9️⃣ Response
        return response()->json([
            'status' => 'success',
            'message' => $kind === 'employee'
                ? 'Login successful (Employee)'
                : 'Login successful',
            'confidence' => round($bestScore, 4),
            'token' => $token,
            'user' => [
                'id' => $bestUser->id,
                'name' => $bestUser->name,
                'email' => $bestUser->email,
                'mobile' => $bestUser->mobile_number,
                'user_type' => $bestUser->userType?->name,
                'roles' => $bestUser->roles?->pluck('role_id') ?? [],
                'apps' => $bestUser->appAccesses?->map(fn($app) => [
                    'app_id' => $app->app_id,
                    'is_enabled' => $app->is_enabled,
                ]) ?? [],
                'identities' => $bestUser->identityLinks?->map(fn($link) => [
                    'kind' => $link->kind,
                    'student_number' => $link->student_number,
                    'employee_number' => $link->employee_number,
                    'guest_number' => $link->guest_number,
                ]) ?? [],
                'student_profile' => $studentProfile,
                'enrollment_history' => $enrollmentHistory,
                'employee_profile' => $employeeProfile,
            ],
        ]);
    } catch (\Throwable $e) {
        \Log::error('Face login error', ['error' => $e->getMessage()]);
        return response()->json([
            'status' => 'error',
            'message' => 'Face login failed.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

/**
 * Normalize embedding vector to unit length
 */
private function normalizeVector(array $v): array
{
    $norm = sqrt(array_sum(array_map(fn($x) => $x * $x, $v)));
    return $norm ? array_map(fn($x) => $x / $norm, $v) : $v;
}

/**
 * Compute cosine similarity between two vectors
 */
private function cosineSimilarity(array $a, array $b): float
{
    $dot = 0; $normA = 0; $normB = 0;
    $len = min(count($a), count($b));

    for ($i = 0; $i < $len; $i++) {
        $dot   += $a[$i] * $b[$i];
        $normA += $a[$i] ** 2;
        $normB += $b[$i] ** 2;
    }

    if ($normA == 0 || $normB == 0) {
        return 0.0;
    }

    return $dot / (sqrt($normA) * sqrt($normB));
}


public function removeFace(Request $request)
{
    $request->validate([
        'user_id' => 'required|integer|exists:users,id',
    ]);

    try {
        $user = Users::with('faceid')->findOrFail($request->user_id);

        if (!$user->faceid) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No Face ID linked for this user.',
            ], 404);
        }

        $face = $user->faceid;

        // 🔹 Delete stored face image file (if exists)
        if ($face->face_image_path) {
            $relativePath = str_replace('/storage/', '', $face->face_image_path);
            if (\Storage::disk('public')->exists($relativePath)) {
                \Storage::disk('public')->delete($relativePath);
                \Log::info("Deleted face image: {$relativePath}");
            }
        }

        // 🔹 Delete the FaceId record
        $face->delete();

        // 🔹 Update user record
        $user->update([
            'faceid_id' => null,
            'faceid_enabled' => false,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Face ID removed successfully, including stored image.',
        ]);
    } catch (\Throwable $e) {
        \Log::error('Face remove error', ['error' => $e->getMessage()]);
        return response()->json([
            'status'  => 'error',
            'message' => 'Failed to remove Face ID.',
            'debug' => $e->getMessage(),
        ], 500);
    }
}



}