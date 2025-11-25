<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VoucherCodeCheckResource;
use App\Models\VoucherCode;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function checkVoucherCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'category_ticket_type_id' => 'required|integer|exists:category_ticket_type,id',
        ]);

        $voucherCode = VoucherCode::with([
                'voucher.categoryTicketType.ticketType',
                'registrations'
            ])
            ->where('code', $request->code)
            ->whereHas('voucher.categoryTicketType', function ($query) use ($request) {
                $query->where('id', $request->category_ticket_type_id);
            })
            ->first();

        if (!$voucherCode) {
            return response()->json(['message' => 'Kode voucher tidak ditemukan.', 'data' => []], 404);
        }

        $voucher = $voucherCode->voucher;

        // --- VALIDASI SINGLE USE ---
        if (!$voucher->is_multiple_use) {
            // single use hanya boleh dipakai jika used = false
            if ($voucherCode->used) {
                return response()->json([
                    'message' => 'Kode voucher sudah digunakan.',
                    'data' => []
                ], 400);
            }
        }

        // --- VALIDASI MULTI USE ---
        if ($voucher->is_multiple_use) {
            $usedCount = $voucherCode->registrations()->count();

            if ($usedCount >= $voucher->max_usage) {
                return response()->json([
                    'message' => 'Voucher sudah mencapai batas penggunaan.',
                    'data' => []
                ], 400);
            }
        }

        return new VoucherCodeCheckResource($voucherCode);
    }
}
