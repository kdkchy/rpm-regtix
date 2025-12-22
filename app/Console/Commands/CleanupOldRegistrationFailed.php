<?php

namespace App\Console\Commands;

use App\Models\RegistrationFailed;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupOldRegistrationFailed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-old-registration-failed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup registration_failed yang sudah lebih dari 90 hari';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting cleanup old registration_failed...');
        
        // Cari data yang sudah > 90 hari dan belum di-restore
        $cutoffDate = now()->subDays(90);
        
        $failedRegistrations = RegistrationFailed::whereNull('restored_at')
            ->where('failed_at', '<', $cutoffDate)
            ->get();
        
        $count = $failedRegistrations->count();
        
        if ($count > 0) {
            foreach ($failedRegistrations as $failed) {
                Log::info('Deleting old registration_failed', [
                    'registration_code' => $failed->registration_code,
                    'failed_at' => $failed->failed_at,
                    'days_old' => now()->diffInDays($failed->failed_at),
                ]);
            }
            
            // Delete dalam batch
            $deleted = RegistrationFailed::whereNull('restored_at')
                ->where('failed_at', '<', $cutoffDate)
                ->delete();
            
            $this->info("Cleanup completed. {$deleted} old registration_failed deleted.");
        } else {
            $this->info('No old registration_failed to cleanup.');
        }
        
        return Command::SUCCESS;
    }
}
