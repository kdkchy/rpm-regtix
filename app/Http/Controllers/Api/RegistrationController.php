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
use Illuminate\Support\Facades\Log;
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
                ->with([
                    'categoryTicketType.ticketType',
                    'categoryTicketType.category',
                    'categoryTicketType.category.event',
                    'voucherCode.voucher'
                ])
                ->first();

            if ($registran) {
                // Jika payment_url null → delete registrasi agar bisa daftar ulang
                if (!$registran->payment_url) {
                    if ($registran->voucherCode) {
                        $registran->voucherCode->update(['used' => false]);
                    }
                    $registran->delete();
                } else {
                    $res = $registran->makeHidden('category_ticket_type')->toArray();
                    $res['payment_url'] = $registran->payment_status === 'paid' 
                        ? config('app.url') . "/payment/finish/{$registran->registration_code}" 
                        : $registran->payment_url;

                    return response()->json([
                        'message' => $registran->payment_status === 'paid' 
                            ? 'Registration exists' 
                            : 'Registration pending exists',
                        'data' => $res
                    ], $registran->payment_status === 'paid' ? 201 : 200);
                }
            }


            // Check remaining quota
            $quota = CategoryTicketType::where('id', $data['category_ticket_type_id'])->value('quota');
            
            // Handle null quota (unlimited quota)
            if ($quota === null) {
                $quota = PHP_INT_MAX; // Treat null as unlimited
            }
            
            $used = Registration::where('category_ticket_type_id', $data['category_ticket_type_id'])
                ->where('payment_status', 'paid')
                ->count();
            $remaining = max($quota - $used, 0);

            // Jika quota sudah habis → return error
            if ($remaining <= 0 && $quota !== PHP_INT_MAX) {
                return response()->json([
                    'message' => 'Quota untuk kategori ini sudah habis. Silahkan daftar pada kategori lain',
                ], 409);
            }

            // ==== VALIDASI VOUCHER SEBELUM CREATE REGISTRATION ====
            $voucherCode = null;
            $voucher = null;
            $voucherValid = false;
            $finalPrice = 0;

            if (!empty($data['voucher_code'])) {
                // Use transaction with lock to prevent race condition
                $voucherValidation = DB::transaction(function () use ($data) {
                    $voucherCode = VoucherCode::where('code', $data['voucher_code'])
                        ->lockForUpdate() // Lock row to prevent race condition
                        ->with('voucher')
                        ->first();

                    if (!$voucherCode) {
                        return ['error' => 'Voucher code not found.', 'code' => 404];
                    }

                    $voucher = $voucherCode->voucher;

                    if (!$voucher) {
                        return ['error' => 'Voucher data invalid.', 'code' => 404];
                    }

                    // ==== Hitung penggunaan voucher - hanya confirmed/paid (Bug fix) ====
                    // Use confirmedRegistrations() instead of registrations() to only count paid registrations
                    $usedCount = $voucherCode->confirmedRegistrations()->count();

                    $voucherValid = false;
                    if ($voucher->is_multiple_use) {
                        $voucherValid = $usedCount < $voucher->max_usage;
                    } else {
                        $voucherValid = !$voucherCode->used;
                    }

                    if (!$voucherValid) {
                        return ['error' => 'Voucher code already used or expired.', 'code' => 400];
                    }

                    return [
                        'voucherCode' => $voucherCode,
                        'voucher' => $voucher,
                        'voucherValid' => $voucherValid
                    ];
                });

                // Check if validation returned error
                if (isset($voucherValidation['error'])) {
                    return response()->json(['message' => $voucherValidation['error']], $voucherValidation['code']);
                }

                $voucherCode = $voucherValidation['voucherCode'];
                $voucher = $voucherValidation['voucher'];
                $voucherValid = $voucherValidation['voucherValid'];
            }

            // ==== Hitung harga final SEBELUM registration dibuat ====
            $categoryTicketType = CategoryTicketType::find($data['category_ticket_type_id']);

            if (!$categoryTicketType) {
                return response()->json(['message' => 'Category ticket type not found.'], 404);
            }

            $ticketType = $categoryTicketType->ticketType;

            if (!$ticketType) {
                return response()->json(['message' => 'Ticket type not found.'], 404);
            }

            if ($voucherValid && $voucher) {
                $finalPrice = floatval($voucher->final_price);
            } else {
                $finalPrice = floatval($categoryTicketType->price);
            }

            // ==== CREATE REGISTRATION ====
            $data['registration_code'] = 'RTIX-' . $event->code_prefix . '-' . strtoupper(Str::random(6));
            $data['registration_date'] = Carbon::now();
            $data['status'] = 'pending';
            $data['payment_status'] = 'pending';
            $data['reg_id'] = "";

            // Use transaction to ensure data consistency
            $registration = DB::transaction(function () use ($data, $voucherValid, $voucherCode) {
                $registration = Registration::create($data);

                // Assign voucher ke registration (setelah semua valid)
                if ($voucherValid && $voucherCode) {
                    $registration->voucher_code_id = $voucherCode->id;
                    $registration->save();
                }

                return $registration;
            });

            // ==== SIAPKAN RESPONSE ====
            $regData = $registration->makeHidden('category_ticket_type')->toArray();

            $category = $categoryTicketType->category;
            if (!$category) {
                return response()->json(['message' => 'Category not found.'], 404);
            }

            $regData['category'] = $category->toArray();
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
                $subject = $event->name . ' - Your Print-At-Home Tickets have arrived! - Do Not Reply';
                $templatePath = resource_path('email/templates/e-ticket.html');
                if (!file_exists($templatePath)) {
                    Log::error('Email template not found', ['path' => $templatePath]);
                    return response()->json(['message' => 'Email template not found.'], 500);
                }
                $template = file_get_contents($templatePath);

                $count = Registration::where('category_ticket_type_id', $registration->category_ticket_type_id)
                    ->where('status', 'confirmed')
                    ->count();

                $regIdGenerator = new GenerateBib();
                $qrGenerator = new QrUtils();

                $regId = $regIdGenerator->generateRegId($count);
                $qrPath = $qrGenerator->generateQr($registration);

                $regData['payment_url'] = config('app.url') . "/payment/finish/{$registration->registration_code}";

                $registration->update([
                    'status' => 'confirmed',
                    'payment_status' => 'paid',
                    'paid_at' => now(),
                    'payment_type' => '',
                    'gross_amount' => 0,
                    'qr_code_path' => $qrPath,
                    'reg_id' => $regId
                ]);

                // Mark voucher as used only for single-use vouchers
                if ($voucher && $voucherCode && !$voucher->is_multiple_use) {
                    $voucherCode->used = true;
                    $voucherCode->save();
                }

            } else {
                // ==== NOT FREE → PAYMENT LINK ====
                $midtrans = new MidtransUtils();
                $payment = $midtrans->generatePaymentLink($registration, $event);

                $regData['payment_url'] = $payment['payment_url'];
                $registration->update(['payment_url' => $payment['payment_url']]);

                $subject = '[' . $event->name . '] Tagihan Pembayaran - Do Not Reply';
                $templatePath = resource_path('email/templates/payment-instruction.html');
                if (!file_exists($templatePath)) {
                    Log::error('Email template not found', ['path' => $templatePath]);
                    return response()->json(['message' => 'Email template not found.'], 500);
                }
                $template = file_get_contents($templatePath);
            }

            // ==== SEND EMAIL ====
            $email = new EmailSender();
            $email->sendEmail($registration, $subject, $template);

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
                ->with(['categoryTicketType.ticketType', 'categoryTicketType.category', 'categoryTicketType.category.event', 'voucherCode.voucher'])
                ->first();

            if (!$reg) {
                return response()->json([
                    'message' => 'Registration not found.'
                ], 404);
            }

            $categoryTicketType = $reg->categoryTicketType;
            if (!$categoryTicketType) {
                return response()->json([
                    'message' => 'Category ticket type not found.'
                ], 404);
            }

            $ticketType = $categoryTicketType->ticketType;
            if (!$ticketType) {
                return response()->json([
                    'message' => 'Ticket type not found.'
                ], 404);
            }

            $category = $categoryTicketType->category;
            if (!$category) {
                return response()->json([
                    'message' => 'Category not found.'
                ], 404);
            }

            $event = $category->event;
            if (!$event) {
                return response()->json([
                    'message' => 'Event not found.'
                ], 404);
            }

            $data = [
                ...$reg->toArray(),
                'category_ticket_type' => [
                    ...$categoryTicketType->toArray(),
                    'ticket_type' => [
                        ...$ticketType->toArray(),
                    ],
                    'category' => [
                        ...$category->toArray(),
                        'event' => [
                            ...$event->toArray(),
                            'event_logo' => $event->event_logo ? asset('storage/' . $event->event_logo) : null,
                            'event_banner' => $event->event_banner ? asset('storage/' . $event->event_banner) : null
                        ]
                    ]
                ]
            ];
            return response()->json([
                'message' => 'Registration found.',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error checking registration', [
                'registration_code' => $request->registration_code ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
