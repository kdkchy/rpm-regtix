<?php

namespace App\Http\Controllers\Api;

use App\Helpers\EmailSender;
use App\Helpers\GenerateBib;
use App\Helpers\MidtransUtils;
use App\Helpers\QrUtils;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRegistrationRequest;
use App\Models\CategoryTicketType;
use App\Models\Event;
use App\Models\Registration;
use App\Models\VoucherCode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegistrationController extends Controller
{
    public function store(StoreRegistrationRequest $request)
    {
        try {
            $data = $request->validated();
            $event = Event::find($data['event_id']);

            if (!$event) {
                return response()->json(['message' => 'Event not found.'], 404);
            }

            // ==== CEK REGISTRATION DUPLIKAT ====
            $registran = Registration::where('email', $data['email'])
                ->where('category_ticket_type_id', $data['category_ticket_type_id'])
                ->where('id_card_number', $data['id_card_number'])
                ->where('status', 'pending')
                ->with([
                    'categoryTicketType.ticketType',
                    'categoryTicketType.category',
                    'categoryTicketType.category.event',
                    'voucherCode.voucher'
                ])
                ->first();

            if ($registran) {
                $dataResponse = [
                    ...$registran->toArray(),
                    'category_ticket_type' => [
                        ...$registran->categoryTicketType->toArray(),
                        'ticket_type' => [
                            ...$registran->categoryTicketType->ticketType->toArray(),
                        ],
                        'category' => [
                            ...$registran->categoryTicketType->category->toArray(),
                            'event' => [
                                ...$registran->categoryTicketType->category->event->toArray(),
                                'event_logo' => asset('storage/' . $registran->categoryTicketType->category->event->event_logo),
                                'event_banner' => asset('storage/' . $registran->categoryTicketType->category->event->event_banner)
                            ]
                        ]
                    ]
                ];
                return response()->json([
                    'message' => 'Registration already exists.',
                    'data' => $dataResponse
                ], 409);
            }

            // ==== VALIDASI CATEGORY TICKET TYPE ====
            $categoryTicketType = CategoryTicketType::find($data['category_ticket_type_id']);

            if (!$categoryTicketType) {
                return response()->json(['message' => 'Category ticket type not found.'], 404);
            }

            // ==== VALIDASI PERIODE TIKET (Bug #8) ====
            $now = Carbon::now();
            if ($categoryTicketType->valid_from && $now->lt(Carbon::parse($categoryTicketType->valid_from))) {
                return response()->json(['message' => 'Tiket belum tersedia.'], 400);
            }
            if ($categoryTicketType->valid_until && $now->gt(Carbon::parse($categoryTicketType->valid_until))) {
                return response()->json(['message' => 'Tiket sudah tidak tersedia.'], 400);
            }

            $ticketType = $categoryTicketType->ticketType;

            // ==== VALIDASI VOUCHER SEBELUM CREATE REGISTRATION ====
            $voucherCode = null;
            $voucher = null;
            $voucherValid = false;
            $finalPrice = floatval($categoryTicketType->price);

            if (!empty($data['voucher_code'])) {
                // ==== Validasi voucher dengan category_ticket_type_id (Bug #2) ====
                $voucherCode = VoucherCode::where('code', $data['voucher_code'])
                    ->whereHas('voucher.categoryTicketType', function ($query) use ($data) {
                        $query->where('id', $data['category_ticket_type_id']);
                    })
                    ->with('voucher')
                    ->first();

                if (!$voucherCode) {
                    return response()->json([
                        'message' => 'Voucher code tidak ditemukan atau tidak berlaku untuk tiket ini.'
                    ], 404);
                }

                $voucher = $voucherCode->voucher;

                if (!$voucher) {
                    return response()->json(['message' => 'Voucher data invalid.'], 404);
                }

                // ==== Hitung penggunaan voucher - hanya confirmed/paid (Bug #1) ====
                if ($voucher->is_multiple_use) {
                    $usedCount = $voucherCode->confirmedRegistrations()->count();
                    $voucherValid = $usedCount < $voucher->max_usage;
                } else {
                    // Single use: cek apakah sudah ada registration yang confirmed/paid (Bug #5)
                    $hasConfirmedRegistration = $voucherCode->confirmedRegistrations()->exists();
                    $voucherValid = !$voucherCode->used && !$hasConfirmedRegistration;
                }

                if (!$voucherValid) {
                    return response()->json(['message' => 'Voucher code already used or expired.'], 400);
                }

                // Set final price jika voucher valid
                $finalPrice = floatval($voucher->final_price);
            }

            // ==== CREATE REGISTRATION DENGAN TRANSACTION (Bug #4 & #6) ====
            try {
                $registration = DB::transaction(function () use ($data, $event, $voucherCode, $voucher, $voucherValid, $finalPrice) {
                    // Lock voucher code untuk mencegah race condition (Bug #4)
                    if ($voucherCode) {
                        $voucherCode = VoucherCode::lockForUpdate()
                            ->where('id', $voucherCode->id)
                            ->first();
                        
                        $voucher = $voucherCode->voucher;
                        
                        // Validasi ulang setelah lock
                        if ($voucher->is_multiple_use) {
                            $usedCount = $voucherCode->confirmedRegistrations()->count();
                            if ($usedCount >= $voucher->max_usage) {
                                throw new \Exception('Voucher code usage limit reached');
                            }
                        } else {
                            $hasConfirmedRegistration = $voucherCode->confirmedRegistrations()->exists();
                            if ($voucherCode->used || $hasConfirmedRegistration) {
                                throw new \Exception('Voucher code already used');
                            }
                        }
                    }

                    // Create registration
                    $data['registration_code'] = 'RTIX-' . $event->code_prefix . '-' . strtoupper(Str::random(6));
                    $data['registration_date'] = Carbon::now();
                    $data['status'] = 'pending';
                    $data['payment_status'] = 'pending';
                    $data['reg_id'] = "";

                    $registration = Registration::create($data);

                    // Assign voucher ke registration
                    if ($voucherValid && $voucherCode) {
                        $registration->voucher_code_id = $voucherCode->id;
                        $registration->save();

                        // Tandai voucher single-use HANYA setelah registration berhasil (Bug #6)
                        // Mark used akan dilakukan di webhook setelah payment success
                    }

                    return $registration;
                });
            } catch (\Exception $e) {
                return response()->json([
                    'message' => $e->getMessage()
                ], 400);
            }

            // ==== SIAPKAN RESPONSE ====
            $regData = $registration->makeHidden('category_ticket_type')->toArray();

            $regData['category'] = $categoryTicketType->category->toArray();
            $regData['category']['event'] = $event->toArray();

            $regData['voucher_code'] = $voucherCode ? [
                ...$voucherCode->toArray(),
                'valid' => $voucherValid
            ] : null;

            $regData['ticket_type'] = [
                'name' => $ticketType->name,
                'price' => $categoryTicketType->price,
                'quota' => $categoryTicketType->quota,
                'used' => $categoryTicketType->registrations_count,
                'remaining' => $categoryTicketType->quota - $categoryTicketType->registrations_count,
                'valid_from' => $categoryTicketType->valid_from,
                'valid_until' => $categoryTicketType->valid_until,
                'final_price' => $finalPrice,
            ];

            // ==== FREE (final_price = 0) → langsung confirmed ====
            if ($finalPrice == 0) {
                try {
                    // Gunakan transaction untuk memastikan semua proses berhasil (Bug #6)
                    DB::transaction(function () use ($registration, $event, $voucherCode, $voucher) {
                        $count = Registration::where('category_ticket_type_id', $registration->category_ticket_type_id)
                            ->where('status', 'confirmed')
                            ->count();

                        $regIdGenerator = new GenerateBib();
                        $qrGenerator = new QrUtils();

                        $regId = $regIdGenerator->generateRegId($count);
                        $qrPath = $qrGenerator->generateQr($registration);

                        // Update registration dengan semua data
                        $registration->update([
                            'status' => 'confirmed',
                            'payment_status' => 'paid',
                            'paid_at' => now(),
                            'payment_type' => '',
                            'gross_amount' => 0,
                            'qr_code_path' => $qrPath,
                            'reg_id' => $regId
                        ]);

                        // Mark voucher single-use HANYA setelah semua berhasil (Bug #6)
                        if ($voucherCode && $voucher && !$voucher->is_multiple_use) {
                            $voucherCode->used = true;
                            $voucherCode->save();
                        }

                        // Send email
                        $subject = $event->name . ' - Your Print-At-Home Tickets have arrived! - Do Not Reply';
                        $template = file_get_contents(resource_path('email/templates/e-ticket.html'));
                        $email = new EmailSender();
                        $email->sendEmail($registration, $subject, $template);
                    });

                    $regData['payment_url'] = "https://regtix.id/payment/finish/{$registration->registration_code}";

                } catch (\Exception $e) {
                    // Jika ada error, transaction akan rollback
                    return response()->json([
                        'message' => 'Failed to process free registration: ' . $e->getMessage()
                    ], 500);
                }
            } else {
                // ==== NOT FREE → PAYMENT LINK ====
                $midtrans = new MidtransUtils();
                $paymment = $midtrans->generatePaymentLink($registration, $event);

                $regData['payment_url'] = $paymment['payment_url'];
                $registration->update(['payment_url' => $paymment['payment_url']]);

                $subject = '[' . $event->name . '] Tagihan Pembayaran - Do Not Reply';
                $template = file_get_contents(resource_path('email/templates/payment-instruction.html'));

                // Send email untuk payment instruction
                $email = new EmailSender();
                $email->sendEmail($registration, $subject, $template);
            }

            return response()->json([
                'message' => 'Registration successful.',
                'data' => $regData
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function checkRegistration(Request $request)
    {
        try {

            $reg = Registration::where('registration_code', $request->registration_code)
                ->orWhere('id_card_number', $request->registration_code)
                ->with(['categoryTicketType.ticketType', 'categoryTicketType.category', 'categoryTicketType.category.event', 'voucherCode.voucher'])
                ->first();

            if (!$reg) {
                return response()->json([
                    'message' => 'Registration not found.'
                ], 404);
            }

            $data = [
                ...$reg->toArray(),
                'category_ticket_type' => [
                    ...$reg->categoryTicketType->toArray(),
                    'ticket_type' => [
                        ...$reg->categoryTicketType->ticketType->toArray(),
                    ],
                    'category' => [
                        ...$reg->categoryTicketType->category->toArray(),
                        'event' => [
                            ...$reg->categoryTicketType->category->event->toArray(),
                            'event_logo' => asset('storage/' . $reg->categoryTicketType->category->event->event_logo),
                            'event_banner' => asset('storage/' . $reg->categoryTicketType->category->event->event_banner)
                        ]
                    ]
                ]
            ];
            return response()->json([
                'message' => 'Registration found.',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
