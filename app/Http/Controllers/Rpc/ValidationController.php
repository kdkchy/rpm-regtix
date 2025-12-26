<?php

namespace App\Http\Controllers\Rpc;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rpc\ValidateRequest;
use App\Http\Resources\Rpc\ValidationResource;
use App\Models\Registration;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ValidationController extends Controller
{
    /**
     * Validasi pengambilan RPC
     *
     * @param ValidateRequest $request
     * @return JsonResponse|ValidationResource
     */
    public function validate(ValidateRequest $request): JsonResponse|ValidationResource
    {
        $participantId = $request->participant_id;
        $operatorId = auth()->id();

        // Get authorized event IDs for the authenticated user
        $authorizedEventIds = auth()->user()->events()->pluck('events.id')->toArray();
        
        // Validasi pertama: Jika user tidak punya authorized events, return error
        if (empty($authorizedEventIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda tidak memiliki akses ke event manapun.',
            ], 403);
        }

        // Gunakan database transaction dengan lock untuk prevent double validate
        $registration = DB::transaction(function () use ($participantId, $operatorId, $authorizedEventIds) {
            // Lock row untuk prevent concurrent updates
            $registration = Registration::lockForUpdate()
                ->with('categoryTicketType.category.event')
                ->find($participantId);

            if (!$registration) {
                return null;
            }

            // Validasi kedua: Cek apakah event_id dari registration ada dalam authorized events
            $registrationEventId = $registration->categoryTicketType?->category?->event_id;
            if (!$registrationEventId || !in_array($registrationEventId, $authorizedEventIds)) {
                return 'unauthorized_event';
            }

            // Cek apakah sudah validated
            if ($registration->is_validated) {
                return 'already_validated';
            }

            // Update status
            $registration->update([
                'is_validated' => true,
                'validated_by' => $operatorId,
            ]);

            // Refresh untuk mendapatkan updated_at terbaru
            $registration->refresh();
            $registration->load('validator');

            return $registration;
        });

        if ($registration === null) {
            return response()->json([
                'success' => false,
                'message' => 'Peserta tidak ditemukan.',
            ], 404);
        }

        if ($registration === 'unauthorized_event') {
            return response()->json([
                'success' => false,
                'message' => 'Peserta tidak dapat divalidasi untuk event ini.',
            ], 403);
        }

        if ($registration === 'already_validated') {
            return response()->json([
                'success' => false,
                'message' => 'Peserta sudah divalidasi.',
            ], 409);
        }

        return new ValidationResource($registration);
    }
}




