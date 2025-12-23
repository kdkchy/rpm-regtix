<?php

namespace App\Console\Commands;

use App\Http\Controllers\EmailCampaignController;
use Illuminate\Console\Command;

class SendEmailCampaign extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-email-campaign';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    public function __construct()
    {
        parent::__construct();
    }
    /**
     * Execute the console command.
     */
    public function handle()
    {
        info("Cron Job running at " . now());
       
        $controller = new EmailCampaignController();
        $controller->sendEmailToRegistrations();
        
    }
}
