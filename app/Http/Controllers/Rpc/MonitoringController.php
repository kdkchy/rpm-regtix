<?php

namespace App\Http\Controllers\Rpc;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MonitoringController extends Controller
{
    /**
     * Get monitoring statistics for an event
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function stats(Request $request): JsonResponse
    {
        $eventId = $request->query('event_id');

        // Get authorized event IDs for the authenticated user
        $user = auth()->user();
        $authorizedEventIds = $user->events()->pluck('events.id')->toArray();

        // Validasi: Jika user tidak punya authorized events, return error
        if (empty($authorizedEventIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda tidak memiliki akses ke event manapun.',
            ], 403);
        }

        // Normalize event_id to integer
        $eventId = $eventId ? (int)$eventId : null;

        // Validasi event_id: Jika diberikan, pastikan ada dalam list authorized events
        if ($eventId && $eventId > 0 && !in_array($eventId, $authorizedEventIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Event tidak dapat diakses.',
            ], 403);
        }

        // If event_id is null, use first authorized event
        if (!$eventId || $eventId == 0) {
            $eventId = $authorizedEventIds[0] ?? null;
        }

        if (!$eventId) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada event yang tersedia.',
            ], 404);
        }

        // Cache key untuk mengurangi beban database
        // Cache dengan TTL 2 detik untuk balance antara real-time dan performance
        $cacheKey = "monitoring_stats_event_{$eventId}";
        $stats = Cache::remember($cacheKey, 2, function () use ($eventId, $authorizedEventIds) {
            return $this->calculateStats($eventId, $authorizedEventIds);
        });
        
        // Ensure event_id is set in response
        if (!isset($stats['event_id'])) {
            $stats['event_id'] = $eventId;
        }

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * SSE stream endpoint for real-time monitoring updates
     *
     * @param Request $request
     * @return StreamedResponse
     */
    public function stream(Request $request): StreamedResponse
    {
        $eventId = $request->query('event_id');

        // Handle authentication - support token in query string for SSE (EventSource limitation)
        $token = $request->query('token') ?? $request->bearerToken();
        
        // If token is in Authorization header, extract it
        if (!$token) {
            $authHeader = $request->header('Authorization');
            if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
                $token = substr($authHeader, 7);
            }
        }
        
        // Authenticate user using token
        $user = null;
        if ($token) {
            $accessToken = PersonalAccessToken::findToken($token);
            if ($accessToken) {
                $user = $accessToken->tokenable;
            }
        }
        
        // Fallback to auth()->user() if token auth failed (for normal requests)
        if (!$user) {
            $user = auth()->user();
        }
        
        if (!$user) {
            return response()->stream(function () {
                echo "event: error\n";
                echo "data: " . json_encode([
                    'success' => false,
                    'message' => 'Unauthorized. Silakan login kembali.',
                ]) . "\n\n";
            }, 401, [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'X-Accel-Buffering' => 'no',
            ]);
        }
        
        $authorizedEventIds = $user->events()->pluck('events.id')->toArray();

        // Validasi: Jika user tidak punya authorized events, return error
        if (empty($authorizedEventIds)) {
            return response()->stream(function () {
                echo "event: error\n";
                echo "data: " . json_encode([
                    'success' => false,
                    'message' => 'Akun Anda tidak memiliki akses ke event manapun.',
                ]) . "\n\n";
            }, 403, [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'X-Accel-Buffering' => 'no',
            ]);
        }

        // Normalize event_id to integer
        $eventId = $eventId ? (int)$eventId : null;

        // Validasi event_id: Jika diberikan, pastikan ada dalam list authorized events
        if ($eventId && $eventId > 0 && !in_array($eventId, $authorizedEventIds)) {
            return response()->stream(function () {
                echo "event: error\n";
                echo "data: " . json_encode([
                    'success' => false,
                    'message' => 'Event tidak dapat diakses.',
                ]) . "\n\n";
            }, 403, [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'X-Accel-Buffering' => 'no',
            ]);
        }

        // If event_id is null, use first authorized event
        if (!$eventId || $eventId == 0) {
            $eventId = $authorizedEventIds[0] ?? null;
        }

        if (!$eventId) {
            return response()->stream(function () {
                echo "event: error\n";
                echo "data: " . json_encode([
                    'success' => false,
                    'message' => 'Tidak ada event yang tersedia.',
                ]) . "\n\n";
            }, 404, [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'X-Accel-Buffering' => 'no',
            ]);
        }

        return response()->stream(function () use ($eventId, $authorizedEventIds) {
            $lastStats = null;
            $heartbeatCounter = 0;
            $maxIterations = 1000; // Safety limit to prevent infinite loops
            $iteration = 0;

            while ($iteration < $maxIterations) {
                $iteration++;
                
                // Check if client is still connected
                if (connection_aborted()) {
                    break;
                }

                try {
                    // Calculate stats
                    $stats = $this->calculateStats($eventId, $authorizedEventIds);

                    // Only send update if data changed or it's a heartbeat
                    $statsJson = json_encode($stats);
                    $hasChanged = $lastStats !== $statsJson;
                    $isHeartbeat = ($heartbeatCounter % 10 === 0); // Every 10 iterations (~30 seconds)

                    if ($hasChanged || $isHeartbeat) {
                        if ($hasChanged) {
                            echo "event: stats\n";
                            echo "data: " . $statsJson . "\n\n";
                            $lastStats = $statsJson;
                        } else {
                            echo "event: heartbeat\n";
                            echo "data: " . json_encode(['timestamp' => now()->toIso8601String()]) . "\n\n";
                        }
                        flush();
                    }

                    $heartbeatCounter++;
                } catch (\Exception $e) {
                    // Log error but continue
                    error_log('Monitoring stream error: ' . $e->getMessage());
                    echo "event: error\n";
                    echo "data: " . json_encode([
                        'success' => false,
                        'message' => 'Error calculating stats: ' . $e->getMessage(),
                    ]) . "\n\n";
                    flush();
                }

                // Sleep for 3 seconds before next update
                sleep(3);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Calculate monitoring statistics for an event
     *
     * @param int $eventId
     * @param array $authorizedEventIds
     * @return array
     */
    private function calculateStats(int $eventId, array $authorizedEventIds): array
    {
        // Ensure event_id is in authorized list
        if (!in_array($eventId, $authorizedEventIds)) {
            return [
                'event_id' => $eventId,
                'participants' => [
                    'validated' => 0,
                    'unvalidated' => 0,
                    'total' => 0,
                    'validation_percentage' => 0,
                ],
                'jersey_sizes' => [],
                'timestamp' => now()->toIso8601String(),
            ];
        }

        // Base query - filter by event through relationship
        // Only filter by event_id since we already validated authorization
        $baseQuery = Registration::whereHas('categoryTicketType.category', function ($q) use ($eventId) {
            $q->where('event_id', $eventId);
        })->whereNotNull('category_ticket_type_id'); // Ensure relationship exists

        // Validasi Peserta
        $validatedCount = (clone $baseQuery)->where('is_validated', true)->count();
        $unvalidatedCount = (clone $baseQuery)->where('is_validated', false)->count();
        $totalParticipants = $validatedCount + $unvalidatedCount;

        // Jersey Size Statistics
        // Use DB::raw for proper aggregation
        $jerseyStats = (clone $baseQuery)
            ->whereNotNull('jersey_size')
            ->selectRaw('
                jersey_size,
                SUM(CASE WHEN is_validated = 1 THEN 1 ELSE 0 END) as taken,
                SUM(CASE WHEN is_validated = 0 THEN 1 ELSE 0 END) as not_taken,
                COUNT(*) as total
            ')
            ->groupBy('jersey_size')
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item->jersey_size => [
                        'size' => $item->jersey_size,
                        'taken' => (int)$item->taken,
                        'not_taken' => (int)$item->not_taken,
                        'total' => (int)$item->total,
                    ]
                ];
            })
            ->toArray();

        // Ensure all jersey sizes are present (S, M, L, XL, XXL)
        // Sort by size order for consistent display
        $allSizes = ['S', 'M', 'L', 'XL', 'XXL'];
        $jerseyBreakdown = [];
        foreach ($allSizes as $size) {
            $jerseyBreakdown[$size] = $jerseyStats[$size] ?? [
                'size' => $size,
                'taken' => 0,
                'not_taken' => 0,
                'total' => 0,
            ];
        }
        
        // Also include any other sizes that might exist in database
        foreach ($jerseyStats as $size => $data) {
            if (!isset($jerseyBreakdown[$size])) {
                $jerseyBreakdown[$size] = $data;
            }
        }

        return [
            'event_id' => $eventId,
            'participants' => [
                'validated' => $validatedCount,
                'unvalidated' => $unvalidatedCount,
                'total' => $totalParticipants,
                'validation_percentage' => $totalParticipants > 0 
                    ? round(($validatedCount / $totalParticipants) * 100, 2) 
                    : 0,
            ],
            'jersey_sizes' => $jerseyBreakdown,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}

