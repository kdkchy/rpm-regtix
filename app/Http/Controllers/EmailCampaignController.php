<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Registration;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\ApiException;
use Brevo\Client\Configuration;
use Brevo\Client\Model\SendSmtpEmail;
use Brevo\Client\Model\SendSmtpEmailTo;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmailCampaignController extends Controller
{
    public function sendEmailToRegistrations()
    {
        $campaign = Campaign::where('status','active')->first();
        $registrations = Registration::whereDoesntHave('campaigns', function ($query) use ($campaign) {
            $query->where('campaign_id', $campaign->id);
        })->take(10)->get();

        foreach ($registrations as $registration) {
            Log::info($registration);
            
            $this->sendEmail($campaign, $registration);
            if ($campaign->registrations()->where('registration_id', $registration->id)->exists()) {
                // Jika pivot sudah ada, update status
                $campaign->registrations()->updateExistingPivot($registration->id, ['status' => 'sent']);
            } else {
                // Jika pivot belum ada, tambahkan data pivot baru
                $campaign->registrations()->attach($registration->id, ['status' => 'sent']);
            }
        }        
    }

    private function sendEmail($campaign, $registration)
    {
        try {
            $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', env('BREVO_API_KEY'));
            
            $apiInstance = new TransactionalEmailsApi(
                new Client(),
                $config
            );

            $params = [
                'name' => $registration['full_name'],
                'reg_id' => $registration->event->code_prefix.$registration['reg_id']
            ];
            $sendSmtpEmail = new SendSmtpEmail([
                'subject' => $campaign->subject,
                'sender' => ['name' => 'RegTix | '.$registration->event->name, 'email' => 'info@regtix.id'],
                'replyTo' => ['name' => 'RegTix | '.$registration->event->name, 'email' => 'info@regtix.id'],
                'to' => [new SendSmtpEmailTo(['email' => $registration->email])], // TODO : Ganti ini
                'htmlContent' => $campaign->html_template,
                'params' => $params
            ]);

            $result = $apiInstance->sendTransacEmail($sendSmtpEmail);
            Log::info($result);
        } catch (ApiException $e){
            Log::error($e->getMessage());
        }
    }

}
