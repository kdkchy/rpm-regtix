<?php

namespace App\Helpers;

use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\ApiException;
use Brevo\Client\Configuration;
use Brevo\Client\Model\SendSmtpEmail;
use Brevo\Client\Model\SendSmtpEmailCc;
use Brevo\Client\Model\SendSmtpEmailTo;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class EmailSender 
{
    // send email
    public function sendEmail($registration, $subject, $template, $overrideEmail = null, $overrideCC = null) {
        try {
            $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', env('BREVO_API_KEY'));

            $apiInstance = new TransactionalEmailsApi(
                new Client(),
                $config
            );
            $event = $registration->categoryTicketType->category->event;
            $category = $registration->categoryTicketType->category;
            $ticketType = $registration->categoryTicketType->ticketType;
            $voucher = $registration->voucherCode->voucher ?? null;
            $categoryTicketType = $registration->categoryTicketType;
            $price = $categoryTicketType->price;
            if ($voucher) {
                $finalPrice = $voucher->final_price;                
                $priceReduction = $categoryTicketType->price - $voucher->final_price;
            } else {
                $finalPrice = $categoryTicketType->price;
                $priceReduction = 0;
            }

            $voucherCode = $registration->voucherCode;

            $params = [
                'name' => $registration->full_name,
                'identity_id' => $registration->id_card_number,
                'gender' => $registration->gender,
                'phone' => $registration->phone,
                'email' => $registration->email,
                'event' => $event->name,
                'distance' => $registration->categoryTicketType->category->distance ? $registration->categoryTicketType->category->distance . ' Km' : '',
                'event_date' => $event->start_date ? Carbon::parse($event->start_date)->format('d M Y') : '-',
                'rpc_start_date' => $event->rpc_start_date ? Carbon::parse($event->rpc_start_date)->format('d M Y') : '-',
                'rpc_end_date' => $event->rpc_end_date ? Carbon::parse($event->rpc_end_date)->format('d M Y') : '-',
                'rpc_times' => $event->rpc_collection_times,
                'location' => $event->location,
                'rpc_location' => $event->rpc_collection_location,
                'rpc_location_url' => $event->rpc_collection_gmaps_url,
                'category' => $category->name,
                'qr_code_path' => $registration->qr_code_path,
                'bib' => $registration->bib_name,
                'bib_number' => $registration->reg_id,
                'jersey_size' => $registration->jersey_size,
                'ticket' => $ticketType->name,
                'registration_code' => $registration->registration_code,
                'transaction_status' => $registration->payment_status,
                'payment_method' => $registration->payment_type,
                'payment_url' => $registration->payment_url,
                'date' => Carbon::parse($registration->created_at)->format('d M Y'),
                'cek_registrasi' => 'https://regtix.id/registrations/' . $registration->registration_code,
                'item' => 'Tiket '. $event->name . ' - ' . $category->name . ' - ' . $ticketType->name,
                'price' => $this->formatMoney($price),
                'price_reduction' => '- '.$this->formatMoney($priceReduction),
                'final_price' => $this->formatMoney($finalPrice),
                'voucher' => ($voucher && $voucherCode) ? $voucherCode->code : 'No Voucher',
                'year' => Carbon::now()->year,
                'ig_url' => $event->ig_url,
                'fb_url' => $event->fb_url,
                'event_callwa' => $event->contact_phone
            ];
            $sendSmtpEmail = new SendSmtpEmail([
                'subject' => $subject,
                'sender' => ['name' => 'RegTix | ' . $event->name, 'email' => env('MAIL_SENDER')],
                'replyTo' => ['name' => 'RegTix | ' . $event->name, 'email' => env('MAIL_REPLY_TO')],
                'to' => [new SendSmtpEmailTo(['email' => $overrideEmail ?? $registration->email])], 
                'cc' => $overrideCC ? [new SendSmtpEmailCc(['email' => $overrideCC])] : null,
                'htmlContent' => $template,
                'params' => $params
            ]);

            $result = $apiInstance->sendTransacEmail($sendSmtpEmail);
            Log::info($result);
        } catch (ApiException $e) {
            Log::error($e->getMessage());
        }
    }

    function formatMoney($angka){
        $hasil_rupiah = "Rp " . number_format($angka,2,',','.');
        return $hasil_rupiah;
    }
}