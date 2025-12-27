<?php

namespace App\Console\Commands;

use App\Models\EmailLog;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\ApiException;
use Brevo\Client\Configuration;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckEmailStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-email-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Poll email status from Brevo API as fallback for emails that haven\'t been updated via webhook';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting email status polling...');

        // Query emails that are still 'sent' and sent more than 30 minutes ago
        $cutoffTime = now()->subMinutes(30);
        
        $emailLogs = EmailLog::where('status', 'sent')
            ->whereNotNull('brevo_message_id')
            ->where('sent_at', '<', $cutoffTime)
            ->limit(50) // Limit to avoid rate limits
            ->get();

        if ($emailLogs->isEmpty()) {
            $this->info('No emails to check.');
            return Command::SUCCESS;
        }

        $this->info("Found {$emailLogs->count()} emails to check.");

        // Initialize Brevo API
        try {
            $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', env('BREVO_API_KEY'));
            $apiInstance = new TransactionalEmailsApi(new Client(), $config);
        } catch (\Exception $e) {
            $this->error('Failed to initialize Brevo API: ' . $e->getMessage());
            Log::error('CheckEmailStatus: Failed to initialize Brevo API', ['error' => $e->getMessage()]);
            return Command::FAILURE;
        }

        $updated = 0;
        $errors = 0;
        $notFound = 0;

        foreach ($emailLogs as $emailLog) {
            try {
                // Query Brevo API for email status
                // Note: Need to check Brevo API documentation for the correct method
                // This is a placeholder - actual implementation depends on Brevo API
                $messageId = $emailLog->brevo_message_id;
                
                // Try to get email content/status from Brevo
                // Method may vary - this needs to be adjusted based on actual Brevo API
                try {
                    $emailContent = $apiInstance->getTransacEmailContent($messageId);
                    
                    // Extract status from response
                    // This is a placeholder - actual structure needs to be verified
                    $events = $emailContent->getEvents() ?? [];
                    
                    // Find the latest event with status we care about
                    $latestEvent = null;
                    $allowedStatuses = ['delivered', 'bounced', 'invalid', 'hardBounce', 'softBounce'];
                    
                    foreach ($events as $event) {
                        $eventType = is_object($event) && method_exists($event, 'getType') 
                            ? $event->getType() 
                            : ($event['type'] ?? null);
                        
                        if ($eventType && in_array($eventType, $allowedStatuses)) {
                            $latestEvent = $event;
                        }
                    }
                    
                    if ($latestEvent) {
                        $eventType = is_object($latestEvent) && method_exists($latestEvent, 'getType')
                            ? $latestEvent->getType()
                            : ($latestEvent['type'] ?? null);
                        
                        if ($eventType) {
                            $emailLog->updateStatusFromEvent($eventType, [
                                'polled_at' => now()->toIso8601String(),
                                'source' => 'polling',
                            ]);
                            $updated++;
                            $this->info("Updated: {$emailLog->email} -> {$eventType}");
                        }
                    } else {
                        // No status change yet
                        $this->line("No update: {$emailLog->email} (still sent)");
                    }
                } catch (ApiException $e) {
                    if ($e->getCode() === 404) {
                        $notFound++;
                        $this->warn("Not found in Brevo: {$emailLog->email} (messageId: {$messageId})");
                        Log::warning('CheckEmailStatus: Email not found in Brevo', [
                            'email_log_id' => $emailLog->id,
                            'message_id' => $messageId,
                        ]);
                    } else {
                        $errors++;
                        $this->error("API Error for {$emailLog->email}: {$e->getMessage()}");
                        Log::error('CheckEmailStatus: Brevo API error', [
                            'email_log_id' => $emailLog->id,
                            'error' => $e->getMessage(),
                            'code' => $e->getCode(),
                        ]);
                        
                        // If rate limited, stop processing
                        if ($e->getCode() === 429) {
                            $this->warn('Rate limit reached. Stopping polling.');
                            break;
                        }
                    }
                }
            } catch (\Exception $e) {
                $errors++;
                $this->error("Error processing {$emailLog->email}: {$e->getMessage()}");
                Log::error('CheckEmailStatus: Processing error', [
                    'email_log_id' => $emailLog->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Polling completed. Updated: {$updated}, Not found: {$notFound}, Errors: {$errors}");
        
        return Command::SUCCESS;
    }
}
