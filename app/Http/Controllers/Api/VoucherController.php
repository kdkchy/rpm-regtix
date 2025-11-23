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


        $voucherCode = VoucherCode::with(['voucher.categoryTicketType.ticketType'])
            ->where('code', $request->code)
            ->where(function ($q) {
                $q->where('used', false)
                ->orWhereHas('voucher', function ($v) {
                    $v->where('is_multiple_use', true);
                });
            })
            ->whereHas('voucher.categoryTicketType', function ($query) use ($request) {
                $query->where('id', $request->category_ticket_type_id);
            })
            ->whereHas('voucher', function ($q) {
                $q->where('is_multiple_use', false);
            })
            ->first();

        if (!$voucherCode) {
            return response()->json(['message' => 'Kode voucher tidak ditemukan.', 'data' => []], 404);
        }

        return new VoucherCodeCheckResource($voucherCode);
    }
}
