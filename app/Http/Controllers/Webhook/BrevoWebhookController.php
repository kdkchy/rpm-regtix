<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BrevoWebhookController extends Controller
{
    /**
     * Handle webhook from Brevo for email events.
     */
    public function handle(Request $request)
    {
        // Security: Validate Basic Auth or Bearer Token
        if (!$this->validateAuth($request)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $data = $request->all();
        
        // Log full payload for debugging
        Log::info('Brevo webhook received', ['payload' => $data]);

        // Extract event type and messageId from payload
        // Format may vary, so we try multiple possibilities
        $eventType = $data['event'] ?? $data['eventType'] ?? $data['type'] ?? null;
        $messageId = $data['message-id'] ?? $data['messageId'] ?? $data['message_id'] ?? null;
        $email = $data['email'] ?? $data['recipient'] ?? null;
        $date = $data['date'] ?? $data['timestamp'] ?? null;

        // Only process important event types
        $allowedEvents = ['delivered', 'bounced', 'invalid', 'hardBounce', 'softBounce'];
        
        if (!$eventType || !in_array($eventType, $allowedEvents)) {
            Log::info('Brevo webhook event ignored', [
                'event' => $eventType,
                'reason' => 'Not in allowed events list'
            ]);
            return response()->json(['message' => 'Event ignored'], 200);
        }

        if (!$messageId && !$email) {
            Log::warning('Brevo webhook missing messageId and email', ['payload' => $data]);
            return response()->json(['error' => 'Missing messageId or email'], 400);
        }

        // Find email log by messageId (priority) or email + sent_at
        $emailLog = null;
        
        if ($messageId) {
            $emailLog = EmailLog::where('brevo_message_id', $messageId)->first();
        }

        // Fallback: find by email if messageId not found
        if (!$emailLog && $email) {
            // Find most recent sent email log for this email (within last 24 hours)
            $emailLog = EmailLog::where('email', $email)
                ->where('status', 'sent')
                ->where('sent_at', '>=', now()->subDay())
                ->orderBy('sent_at', 'desc')
                ->first();
        }

        if (!$emailLog) {
            Log::warning('Brevo webhook: Email log not found', [
                'messageId' => $messageId,
                'email' => $email,
                'event' => $eventType,
            ]);
            return response()->json(['error' => 'Email log not found'], 404);
        }

        // Update email log status
        try {
            $oldStatus = $emailLog->status;
            
            $emailLog->updateStatusFromEvent($eventType, [
                'webhook_payload' => $data,
                'webhook_received_at' => now()->toIso8601String(),
            ]);

            $emailLog->refresh();
            
            Log::info('Brevo webhook: Email log updated', [
                'email_log_id' => $emailLog->id,
                'registration_id' => $emailLog->registration_id,
                'old_status' => $oldStatus,
                'new_status' => $emailLog->status,
                'event' => $eventType,
            ]);

            return response()->json(['message' => 'Success'], 200);
        } catch (\Exception $e) {
            Log::error('Brevo webhook: Failed to update email log', [
                'email_log_id' => $emailLog->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Failed to update'], 500);
        }
    }

    /**
     * Validate authentication for webhook.
     */
    private function validateAuth(Request $request): bool
    {
        // Option 1: Basic Auth
        $basicAuthUser = env('BREVO_WEBHOOK_USER');
        $basicAuthPass = env('BREVO_WEBHOOK_PASS');
        
        if ($basicAuthUser && $basicAuthPass) {
            $user = $_SERVER['PHP_AUTH_USER'] ?? null;
            $pass = $_SERVER['PHP_AUTH_PW'] ?? null;
            
            if ($user === $basicAuthUser && $pass === $basicAuthPass) {
                return true;
            }
        }

        // Option 2: Bearer Token
        $token = env('BREVO_WEBHOOK_TOKEN');
        if ($token) {
            $bearerToken = $request->bearerToken();
            if ($bearerToken === $token) {
                return true;
            }
        }

        // If no auth configured, allow (for development/testing)
        // In production, at least one auth method should be configured
        if (!$basicAuthUser && !$token) {
            Log::warning('Brevo webhook: No authentication configured');
            return true; // Allow for now, but log warning
        }

        return false;
    }
}

