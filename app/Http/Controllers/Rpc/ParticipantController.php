<?php

namespace App\Http\Controllers\Rpc;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rpc\SearchParticipantRequest;
use App\Http\Resources\Rpc\ParticipantResource;
use App\Models\Registration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class ParticipantController extends Controller
{
    /**
     * Search peserta untuk keperluan validasi
     *
     * @param SearchParticipantRequest $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function search(SearchParticipantRequest $request): JsonResponse|AnonymousResourceCollection
    {
        $keyword = trim($request->keyword);
        $eventId = $request->event_id;
        $status = $request->status;

        // Get authorized event IDs for the authenticated user
        $user = auth()->user();
        $authorizedEventIds = $user->events()->pluck('events.id')->toArray();
        
        // Validasi penting: Jika user tidak punya event yang diotorisasi, return error
        if (empty($authorizedEventIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda tidak memiliki akses ke event manapun. Silakan hubungi administrator.',
            ], 403);
        }
        
        // Normalize event_id to integer (handle string input)
        $eventId = $eventId ? (int)$eventId : null;

        // #region agent log
        $logFile = 'd:\REGTIX\.cursor\debug.log';
        $logData = [
            'id' => 'log_' . time() . '_' . uniqid(),
            'timestamp' => round(microtime(true) * 1000),
            'location' => 'ParticipantController.php:22',
            'message' => 'Search request received',
            'data' => ['keyword' => $keyword, 'eventId' => $eventId, 'status' => $status, 'keywordLength' => strlen($keyword)],
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'H1'
        ];
        @file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
        Log::info('ParticipantController search', $logData['data']);
        // #endregion

        // Normalize event_id to integer
        $eventId = $eventId ? (int)$eventId : null;
        
        // Validasi event_id request: Jika diberikan, pastikan ada dalam list authorized events
        if ($eventId && $eventId > 0 && !in_array($eventId, $authorizedEventIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Event tidak dapat diakses untuk pencarian ini.',
            ], 403);
        }

        // If event_id is null or 0, try to find event_id from registrations that match keyword (only in 4 fields)
        // But only search within authorized events
        if (!$eventId || $eventId == 0) {
            $sampleRegistration = Registration::where(function ($q) use ($keyword) {
                $q->where('full_name', 'like', "%{$keyword}%")
                    ->orWhere('registration_code', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            })
            ->whereHas('categoryTicketType.category', function ($q) use ($authorizedEventIds) {
                $q->whereIn('event_id', $authorizedEventIds);
            })
            ->with('categoryTicketType.category.event')
            ->first();
            
            if ($sampleRegistration && $sampleRegistration->categoryTicketType?->category?->event_id) {
                $detectedEventId = $sampleRegistration->categoryTicketType->category->event_id;
                // Only use detected event_id if it's in authorized events
                if (in_array($detectedEventId, $authorizedEventIds)) {
                    $eventId = $detectedEventId;
                    error_log('ParticipantController: Auto-detected event_id: ' . $eventId);
                }
            }
        }

        // #region agent log - Check if keyword matches any registration at all (only in 4 fields)
        $totalRegistrationsWithKeyword = Registration::where(function ($q) use ($keyword) {
            $q->where('full_name', 'like', "%{$keyword}%")
                ->orWhere('registration_code', 'like', "%{$keyword}%")
                ->orWhere('phone', 'like', "%{$keyword}%")
                ->orWhere('email', 'like', "%{$keyword}%");
        })->count();
        $logDataCheck = [
            'id' => 'log_' . time() . '_' . uniqid(),
            'timestamp' => round(microtime(true) * 1000),
            'location' => 'ParticipantController.php:43',
            'message' => 'Total registrations with keyword (no event filter)',
            'data' => ['totalRegistrationsWithKeyword' => $totalRegistrationsWithKeyword, 'keyword' => $keyword],
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'H2'
        ];
        @file_put_contents($logFile, json_encode($logDataCheck) . "\n", FILE_APPEND | LOCK_EX);
        error_log('ParticipantController: Total registrations with keyword "' . $keyword . '": ' . $totalRegistrationsWithKeyword);
        // #endregion

        // Build query dengan eager loading
        // Filter berdasarkan authorized events
        $query = Registration::with([
                'categoryTicketType.category.event',
                'categoryTicketType.ticketType',
            ]);
        
        // Filter by authorized events + selected event_id if provided
        // IMPORTANT: whereHas harus diletakkan SEBELUM where clause untuk keyword
        $query->whereHas('categoryTicketType.category', function ($q) use ($authorizedEventIds, $eventId) {
            if ($eventId && $eventId > 0 && in_array($eventId, $authorizedEventIds)) {
                $q->where('event_id', $eventId);
            } else {
                $q->whereIn('event_id', $authorizedEventIds);
            }
        });

        // #region agent log - Check registrations with event_id but without keyword filter
        $registrationsWithEventId = 0;
        if ($eventId && $eventId > 0) {
            $registrationsWithEventId = Registration::whereHas('categoryTicketType.category', function ($q) use ($eventId) {
                $q->where('event_id', $eventId);
            })->count();
        }
        $logDataEvent = [
            'id' => 'log_' . time() . '_' . uniqid(),
            'timestamp' => round(microtime(true) * 1000),
            'location' => 'ParticipantController.php:60',
            'message' => 'Total registrations with event_id',
            'data' => ['registrationsWithEventId' => $registrationsWithEventId, 'eventId' => $eventId],
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'H2'
        ];
        @file_put_contents($logFile, json_encode($logDataEvent) . "\n", FILE_APPEND | LOCK_EX);
        error_log('ParticipantController: Total registrations with event_id ' . $eventId . ': ' . $registrationsWithEventId);
        // #endregion

        // #region agent log
        $logData = [
            'id' => 'log_' . time() . '_' . uniqid(),
            'timestamp' => round(microtime(true) * 1000),
            'location' => 'ParticipantController.php:70',
            'message' => 'Query after whereHas',
            'data' => ['sql' => $query->toSql(), 'bindings' => $query->getBindings()],
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'H2'
        ];
        @file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
        Log::info('ParticipantController query after whereHas', $logData['data']);
        // #endregion

        // Build search conditions - hanya 4 field: no tiket, nama peserta, no telp, alamat email
        $query->where(function ($q) use ($keyword) {
            $q->where('full_name', 'like', "%{$keyword}%")           // Nama peserta
                ->orWhere('registration_code', 'like', "%{$keyword}%") // No tiket
                ->orWhere('phone', 'like', "%{$keyword}%")             // No telp
                ->orWhere('email', 'like', "%{$keyword}%");            // Alamat email
        });

        // Filter by status jika diberikan
        if ($status === 'VALIDATED') {
            $query->where('is_validated', true);
        } elseif ($status === 'NOT_VALIDATED') {
            $query->where('is_validated', false);
        }

        // #region agent log
        $logData = [
            'id' => 'log_' . time() . '_' . uniqid(),
            'timestamp' => round(microtime(true) * 1000),
            'location' => 'ParticipantController.php:75',
            'message' => 'Query before execution',
            'data' => ['sql' => $query->toSql(), 'bindings' => $query->getBindings(), 'statusFilter' => $status],
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'H3'
        ];
        file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
        Log::info('ParticipantController query before execution', $logData['data']);
        // #endregion

        $participants = $query->limit(50)->get();
        $participantCount = $participants->count();

        // #region agent log
        $logData = [
            'id' => 'log_' . time() . '_' . uniqid(),
            'timestamp' => round(microtime(true) * 1000),
            'location' => 'ParticipantController.php:104',
            'message' => 'Query results',
            'data' => [
                'count' => $participantCount, 
                'firstParticipant' => $participants->first() ? [
                    'id' => $participants->first()->id, 
                    'full_name' => $participants->first()->full_name,
                    'is_validated' => $participants->first()->is_validated
                ] : null
            ],
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'H4'
        ];
        @file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
        Log::info('ParticipantController query results', $logData['data']);
        error_log('ParticipantController: Query returned ' . $participantCount . ' results');
        
        // Check if query returns results without status filter (only in 4 fields)
        $queryWithoutStatus = Registration::with(['categoryTicketType.category.event', 'categoryTicketType.ticketType'])
            ->where(function ($q) use ($keyword) {
                $q->where('full_name', 'like', "%{$keyword}%")
                    ->orWhere('registration_code', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            })
            ->whereHas('categoryTicketType.category', function ($q) use ($authorizedEventIds, $eventId) {
                if ($eventId && $eventId > 0 && in_array($eventId, $authorizedEventIds)) {
                    $q->where('event_id', $eventId);
                } else {
                    $q->whereIn('event_id', $authorizedEventIds);
                }
            });
        
        $countWithoutStatus = $queryWithoutStatus->count();
        $logData2 = [
            'id' => 'log_' . time() . '_' . uniqid(),
            'timestamp' => round(microtime(true) * 1000),
            'location' => 'ParticipantController.php:130',
            'message' => 'Query without status filter',
            'data' => ['countWithoutStatus' => $countWithoutStatus, 'countWithStatus' => $participantCount],
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'H3'
        ];
        @file_put_contents($logFile, json_encode($logData2) . "\n", FILE_APPEND | LOCK_EX);
        error_log('ParticipantController: Without status filter: ' . $countWithoutStatus . ', With status filter: ' . $participantCount);
        // #endregion

        $collection = ParticipantResource::collection($participants);
        $collectionArray = $collection->collection->toArray();
        
        // #region agent log
        $logData = [
            'id' => 'log_' . time() . '_' . uniqid(),
            'timestamp' => round(microtime(true) * 1000),
            'location' => 'ParticipantController.php:150',
            'message' => 'Response data',
            'data' => [
                'collectionCount' => count($collectionArray), 
                'firstItem' => $collectionArray[0] ?? null,
                'countWithoutStatus' => $countWithoutStatus ?? null
            ],
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'H6'
        ];
        @file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
        Log::info('ParticipantController response data', $logData['data']);
        error_log('ParticipantController: Returning ' . count($collectionArray) . ' items in response');
        // #endregion
        
        return response()->json([
            'success' => true,
            'message' => 'Pencarian peserta berhasil.',
            'data' => $collectionArray,
            'debug' => [
                'count' => count($collectionArray),
                'countWithoutStatusFilter' => $countWithoutStatus ?? 0,
                'statusFilter' => $status,
                'totalRegistrationsWithKeyword' => $totalRegistrationsWithKeyword ?? 0,
                'registrationsWithEventId' => $registrationsWithEventId ?? 0,
                'eventId' => $eventId,
                'keyword' => $keyword
            ]
        ]);
    }
}

