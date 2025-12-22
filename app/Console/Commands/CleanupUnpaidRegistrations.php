<?php

namespace App\Console\Commands;

use App\Models\Registration;
use App\Services\RegistrationFailedService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupUnpaidRegistrations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-unpaid-registrations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup unpaid registrations yang sudah lebih dari 24 jam';

    /**
     * Execute the console command.
     */
    public function handle(RegistrationFailedService $service)
    {
        $this->info('Starting cleanup unpaid registrations...');
        
        // Cari registration yang sudah > 24 jam dan masih unpaid
        $cutoffTime = now()->subHours(24);
        
        $registrations = Registration::whereIn('payment_status', ['pending', 'expire'])
            ->where(function($query) use ($cutoffTime) {
                $query->where('registration_date', '<', $cutoffTime)
                      ->orWhere('created_at', '<', $cutoffTime);
            })
            ->get();
        
        $count = 0;
        $errors = 0;
        
        foreach ($registrations as $registration) {
            try {
                // Backup ke registration_failed
                $service->backupFailedRegistration($registration, 'expired_unpaid');
                
                // Delete dari registrations
                $registration->delete();
                
                $count++;
                $this->info("Backed up and deleted: {$registration->registration_code}");
                
                Log::info('Registration cleaned up', [
                    'registration_code' => $registration->registration_code,
                    'registration_date' => $registration->registration_date,
                ]);
            } catch (\Exception $e) {
                $errors++;
                $this->error("Failed to cleanup {$registration->registration_code}: {$e->getMessage()}");
                Log::error('Failed to cleanup registration', [
                    'registration_code' => $registration->registration_code,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
        
        if ($count > 0) {
            $this->info("Cleanup completed. {$count} registrations processed.");
        } else {
            $this->info('No unpaid registrations to cleanup.');
        }
        
        if ($errors > 0) {
            $this->warn("{$errors} errors occurred during cleanup.");
        }
        
        return Command::SUCCESS;
    }
}
