<?php

namespace App\Http\Controllers\Rpc;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rpc\PrintPayloadRequest;
use App\Http\Resources\Rpc\PrintPayloadResource;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PrintController extends Controller
{
    /**
     * Get print payload untuk template cetak
     *
     * @param PrintPayloadRequest $request
     * @return JsonResponse|PrintPayloadResource
     */
    public function getPayload(PrintPayloadRequest $request): JsonResponse|PrintPayloadResource
    {
        try {
            $printType = $request->print_type;
            $participantIds = $request->participant_ids;
            $representativeData = $request->representative_data;
            $operator = $request->user(); // User yang sedang login

            // Get authorized event IDs for the authenticated user
            $authorizedEventIds = $operator->events()->pluck('events.id')->toArray();
            
            // Validasi pertama: Jika user tidak punya authorized events, return error
            if (empty($authorizedEventIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun Anda tidak memiliki akses ke event manapun.',
                ], 403);
            }

            // Ambil participants dari database dengan relasi event
            $participants = Registration::whereIn('id', $participantIds)
                ->with([
                    'categoryTicketType.category.event',
                    'categoryTicketType.ticketType',
                ])
                ->get();

            if ($participants->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Peserta tidak ditemukan.',
                ], 404);
            }

            // AMBIL event_name dari peserta pertama (semua peserta harus dari event yang sama)
            // Ini adalah sumber utama untuk event_name, bukan dari event_id yang dikirim
            $firstParticipant = $participants->first();
            $eventFromParticipant = $firstParticipant->categoryTicketType?->category?->event;
            
            if (!$eventFromParticipant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Event tidak ditemukan untuk peserta ini.',
                ], 404);
            }

            // Validasi kedua: Cek apakah event_id dari participant ada dalam authorized events
            $finalEventId = $eventFromParticipant->id;
            if (!in_array($finalEventId, $authorizedEventIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Event tidak dapat diakses untuk print ini.',
                ], 403);
            }

            // Validasi tambahan: Pastikan semua participants berasal dari event yang sama
            foreach ($participants as $participant) {
                $participantEventId = $participant->categoryTicketType?->category?->event_id;
                if (!$participantEventId || $participantEventId !== $finalEventId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Semua peserta harus berasal dari event yang sama.',
                    ], 400);
                }
            }

            // GUNAKAN event_name dan event_id dari peserta
            $finalEventName = $eventFromParticipant->name;

            // Format participants untuk print template
            $formattedParticipants = $participants->map(function ($registration) {
            // Handle dob: bisa berupa Carbon object atau string
            $dob = null;
            if ($registration->dob) {
                if (is_string($registration->dob)) {
                    // Jika sudah string, gunakan langsung (asumsi format sudah Y-m-d)
                    $dob = $registration->dob;
                } elseif (method_exists($registration->dob, 'format')) {
                    // Jika Carbon/DateTime object, format ke Y-m-d
                    $dob = $registration->dob->format('Y-m-d');
                } else {
                    // Fallback: convert ke string
                    $dob = (string) $registration->dob;
                }
            }
            
            return [
                'participant_id' => $registration->id,
                'name' => $registration->full_name,
                'ktp_number' => $registration->id_card_number,
                'dob' => $dob,
                'gender' => $registration->gender,
                'address' => $registration->address,
                'phone' => $registration->phone,
                'emergency_contact_name' => $registration->emergency_contact_name ?? '',
                'emergency_contact_phone' => $registration->emergency_contact_phone ?? '',
                'registration_code' => $registration->registration_code,
                'ticket_category' => $registration->categoryTicketType?->category?->name ?? '',
                'ticket_type' => $registration->categoryTicketType?->ticketType?->name ?? '',
                'jersey_size' => $registration->jersey_size ?? '',
                'bib_number' => $registration->reg_id ?? '',
            ];
            })->toArray();

            // Format representative jika power_of_attorney
            $formattedRepresentative = null;
            if ($printType === 'power_of_attorney' && $representativeData) {
                $formattedRepresentative = [
                    'name' => $representativeData['name'] ?? '',
                    'ktp_number' => $representativeData['ktp_number'] ?? '',
                    'dob' => $representativeData['dob'] ?? '',
                    'address' => $representativeData['address'] ?? '',
                    'phone' => $representativeData['phone'] ?? '',
                    'relationship' => $representativeData['relationship'] ?? '',
                ];
            }

            // Prepare payload data
            // GUNAKAN event_name dari peserta, bukan dari event_id yang dikirim
            $payloadData = (object) [
                'print_type' => $printType,
                'participants' => $formattedParticipants,
                'representative' => $formattedRepresentative,
                'operator_id' => $operator->id,
                'operator_name' => $operator->name ?? $operator->email,
                'event_id' => $finalEventId,
                'event_name' => $finalEventName, // Ambil dari peserta, bukan dari event_id
            ];

            return new PrintPayloadResource($payloadData);
        } catch (\Exception $e) {
            // Log error untuk debugging
            Log::error('PrintController getPayload error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil payload cetak: ' . $e->getMessage(),
            ], 500);
        }
    }
}
