<?php

namespace App\Services;

use App\Models\EmailAnnouncement;
use App\Models\EmailLog;
use App\Models\Event;
use App\Models\Registration;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\ApiException;
use Brevo\Client\Configuration;
use Brevo\Client\Model\SendSmtpEmail;
use Brevo\Client\Model\SendSmtpEmailTo;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class EmailAnnouncementService
{

    /**
     * Send email blast to all paid registrations in an event
     *
     * @param int $eventId
     * @param string $subject
     * @param string $htmlTemplate
     * @param int $createdBy
     * @return EmailAnnouncement
     */
    public function sendEmailBlast(int $eventId, string $subject, string $htmlTemplate, int $createdBy): EmailAnnouncement
    {
        $event = Event::findOrFail($eventId);

        // Get all paid registrations for this event
        $registrations = Registration::where('payment_status', 'paid')
            ->whereHas('categoryTicketType.category.event', function ($query) use ($eventId) {
                $query->where('events.id', $eventId);
            })
            ->with(['categoryTicketType.category.event', 'categoryTicketType.category', 'categoryTicketType.ticketType', 'voucherCode.voucher'])
            ->get();

        $totalRecipients = $registrations->count();

        if ($totalRecipients === 0) {
            throw new \Exception('No paid registrations found for this event.');
        }

        // Create email announcement record
        $announcement = EmailAnnouncement::create([
            'event_id' => $eventId,
            'subject' => $subject,
            'html_template' => $htmlTemplate,
            'status' => 'sending',
            'total_recipients' => $totalRecipients,
            'sent_count' => 0,
            'failed_count' => 0,
            'created_by' => $createdBy,
            'sent_at' => now(),
        ]);

        $sentCount = 0;
        $failedCount = 0;

        // Send email to each registration
        foreach ($registrations as $registration) {
            try {
                // Skip if registration doesn't have required relationships
                if (!$registration->categoryTicketType || !$registration->categoryTicketType->category || !$registration->categoryTicketType->ticketType) {
                    Log::warning('Registration missing required relationships', [
                        'registration_id' => $registration->id,
                    ]);
                    $failedCount++;
                    continue;
                }

                // Process template with variables
                $processedTemplate = $this->processTemplate($htmlTemplate, $registration, $event);
                $processedSubject = $this->processTemplate($subject, $registration, $event);

                // Send email using Brevo API directly
                $emailLog = null;
                try {
                    $emailLog = $this->sendEmail($registration, $event, $processedSubject, $processedTemplate);
                } catch (\Exception $emailError) {
                    // If email sending fails, log and continue
                    Log::error('Failed to send email in email blast', [
                        'registration_id' => $registration->id,
                        'error' => $emailError->getMessage(),
                    ]);
                    throw $emailError; // Re-throw to be caught by outer catch
                }

                // Use syncWithoutDetaching to avoid duplicate key errors
                $announcement->registrations()->syncWithoutDetaching([
                    $registration->id => [
                        'status' => 'sent',
                        'email_log_id' => $emailLog?->id,
                        'sent_at' => now(),
                    ]
                ]);

                $sentCount++;
            } catch (\Exception $e) {
                Log::error('Failed to send email blast to registration', [
                    'registration_id' => $registration->id,
                    'email' => $registration->email,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Use syncWithoutDetaching to avoid duplicate key errors
                $announcement->registrations()->syncWithoutDetaching([
                    $registration->id => [
                        'status' => 'failed',
                        'error_message' => substr($e->getMessage(), 0, 500), // Limit error message length
                        'sent_at' => now(),
                    ]
                ]);

                $failedCount++;
            }
        }

        // Update announcement status
        $announcement->update([
            'status' => $failedCount > 0 && $sentCount === 0 ? 'failed' : 'completed',
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'completed_at' => now(),
        ]);

        return $announcement;
    }

    /**
     * Process template with variables
     *
     * @param string $template
     * @param Registration $registration
     * @param Event $event
     * @return string
     */
    protected function processTemplate(string $template, Registration $registration, Event $event): string
    {
        // Safety checks for relationships
        if (!$registration->categoryTicketType || !$registration->categoryTicketType->category || !$registration->categoryTicketType->ticketType) {
            throw new \Exception('Registration is missing required relationships (categoryTicketType, category, or ticketType)');
        }

        $category = $registration->categoryTicketType->category;
        $ticketType = $registration->categoryTicketType->ticketType;
        $voucher = $registration->voucherCode?->voucher ?? null;
        $categoryTicketType = $registration->categoryTicketType;
        $price = $categoryTicketType->price ?? 0;
        
        if ($voucher && isset($voucher->final_price)) {
            $finalPrice = $voucher->final_price;
            $priceReduction = ($categoryTicketType->price ?? 0) - $voucher->final_price;
        } else {
            $finalPrice = $categoryTicketType->price ?? 0;
            $priceReduction = 0;
        }

        $voucherCode = $registration->voucherCode;

        $variables = [
            '{{name}}' => $registration->full_name,
            '{{email}}' => $registration->email,
            '{{event_name}}' => $event->name,
            '{{registration_code}}' => $registration->registration_code,
            '{{identity_id}}' => $registration->id_card_number,
            '{{gender}}' => $registration->gender,
            '{{phone}}' => $registration->phone,
            '{{event}}' => $event->name,
            '{{distance}}' => ($category->distance ?? null) ? $category->distance . ' Km' : '',
            '{{event_date}}' => $event->start_date ? Carbon::parse($event->start_date)->format('d M Y') : '-',
            '{{rpc_start_date}}' => $event->rpc_start_date ? Carbon::parse($event->rpc_start_date)->format('d M Y') : '-',
            '{{rpc_end_date}}' => $event->rpc_end_date ? Carbon::parse($event->rpc_end_date)->format('d M Y') : '-',
            '{{rpc_times}}' => $event->rpc_collection_times ?? '',
            '{{location}}' => $event->location ?? '',
            '{{rpc_location}}' => $event->rpc_collection_location ?? '',
            '{{rpc_location_url}}' => $event->rpc_collection_gmaps_url ?? '',
            '{{category}}' => $category->name ?? '',
            '{{qr_code_path}}' => $registration->qr_code_path ?? '',
            '{{bib}}' => $registration->bib_name ?? '',
            '{{bib_number}}' => $registration->reg_id ?? '',
            '{{jersey_size}}' => $registration->jersey_size ?? '',
            '{{ticket}}' => $ticketType->name ?? '',
            '{{transaction_status}}' => $registration->payment_status,
            '{{payment_method}}' => $registration->payment_type ?? '',
            '{{payment_url}}' => $registration->payment_url ?? '',
            '{{date}}' => Carbon::parse($registration->created_at)->format('d M Y'),
            '{{cek_registrasi}}' => 'https://regtix.id/registrations/' . $registration->registration_code,
            '{{item}}' => 'Tiket ' . ($event->name ?? '') . ' - ' . ($category->name ?? '') . ' - ' . ($ticketType->name ?? ''),
            '{{price}}' => $this->formatMoney($price),
            '{{price_reduction}}' => '- ' . $this->formatMoney($priceReduction),
            '{{final_price}}' => $this->formatMoney($finalPrice),
            '{{voucher}}' => ($voucher && $voucherCode) ? $voucherCode->code : 'No Voucher',
            '{{year}}' => Carbon::now()->year,
            '{{ig_url}}' => $event->ig_url ?? '',
            '{{fb_url}}' => $event->fb_url ?? '',
            '{{event_callwa}}' => $event->contact_phone ?? '',
        ];

        $processedTemplate = $template;
        foreach ($variables as $key => $value) {
            $processedTemplate = str_replace($key, $value, $processedTemplate);
        }

        return $processedTemplate;
    }

    /**
     * Send email using Brevo API
     *
     * @param Registration $registration
     * @param Event $event
     * @param string $subject
     * @param string $htmlContent
     * @return EmailLog|null
     */
    protected function sendEmail(Registration $registration, Event $event, string $subject, string $htmlContent): ?EmailLog
    {
        try {
            $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', env('BREVO_API_KEY'));

            $apiInstance = new TransactionalEmailsApi(
                new Client(),
                $config
            );

            $sendSmtpEmail = new SendSmtpEmail([
                'subject' => $subject,
                'sender' => ['name' => 'RegTix | ' . $event->name, 'email' => env('MAIL_SENDER')],
                'replyTo' => ['name' => 'RegTix | ' . $event->name, 'email' => env('MAIL_REPLY_TO')],
                'to' => [new SendSmtpEmailTo(['email' => $registration->email])],
                'htmlContent' => $htmlContent,
            ]);

            $result = $apiInstance->sendTransacEmail($sendSmtpEmail);
            Log::info('Email blast sent', [
                'registration_id' => $registration->id,
                'email' => $registration->email,
                'result' => $result,
            ]);

            // Extract messageId from response
            $messageId = null;
            if (method_exists($result, 'getMessageId')) {
                $messageId = $result->getMessageId();
            } elseif (isset($result->messageId)) {
                $messageId = $result->messageId;
            } elseif (method_exists($result, 'toArray')) {
                $resultArray = $result->toArray();
                $messageId = $resultArray['messageId'] ?? $resultArray['message-id'] ?? null;
            }

            // Save email log
            $emailLog = EmailLog::create([
                'registration_id' => $registration->id,
                'brevo_message_id' => $messageId,
                'email' => $registration->email,
                'status' => 'sent',
                'sent_at' => now(),
                'status_details' => [
                    'message_id' => $messageId,
                    'subject' => $subject,
                    'type' => 'email_blast',
                ],
            ]);

            return $emailLog;
        } catch (ApiException $e) {
            Log::error('Brevo API Error in email blast', [
                'registration_id' => $registration->id,
                'email' => $registration->email,
                'error' => $e->getMessage(),
            ]);

            // Save error log
            EmailLog::create([
                'registration_id' => $registration->id,
                'email' => $registration->email,
                'status' => 'error',
                'error_message' => $e->getMessage(),
                'sent_at' => now(),
                'status_details' => [
                    'type' => 'email_blast',
                ],
            ]);

            throw $e;
        }
    }

    /**
     * Format money to Indonesian Rupiah format
     *
     * @param float $angka
     * @return string
     */
    protected function formatMoney($angka): string
    {
        return "Rp " . number_format($angka, 2, ',', '.');
    }
}


