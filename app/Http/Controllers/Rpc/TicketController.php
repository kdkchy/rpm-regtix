<?php

namespace App\Http\Controllers\Rpc;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rpc\ScanTicketRequest;
use App\Http\Resources\Rpc\TicketScanResource;
use App\Models\Registration;
use Illuminate\Http\JsonResponse;

class TicketController extends Controller
{
    /**
     * Scan dan verifikasi tiket
     *
     * @param ScanTicketRequest $request
     * @return JsonResponse|TicketScanResource
     */
    public function scan(ScanTicketRequest $request): JsonResponse|TicketScanResource
    {
        $ticketCode = $request->ticket_code;

        // Get authorized event IDs for the authenticated user
        $authorizedEventIds = auth()->user()->events()->pluck('events.id')->toArray();
        
        // Validasi pertama: Jika user tidak punya authorized events, return error
        if (empty($authorizedEventIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda tidak memiliki akses ke event manapun.',
            ], 403);
        }

        // Cari registration by registration_code atau id_card_number
        $registration = Registration::where('registration_code', $ticketCode)
            ->orWhere('id_card_number', $ticketCode)
            ->with([
                'categoryTicketType.category.event',
                'categoryTicketType.ticketType',
            ])
            ->first();

        if (!$registration) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket tidak ditemukan.',
            ], 404);
        }

        // Validasi kedua: Cek apakah event_id dari registration ada dalam authorized events
        $registrationEventId = $registration->categoryTicketType?->category?->event_id;
        if (!$registrationEventId || !in_array($registrationEventId, $authorizedEventIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket tidak dapat diakses untuk event ini.',
            ], 403);
        }

        // Cek apakah sudah validated
        if ($registration->is_validated) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket sudah divalidasi.',
            ], 409);
        }

        return new TicketScanResource($registration);
    }
}




