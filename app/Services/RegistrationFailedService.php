<?php

namespace App\Services;

use App\Models\Registration;
use App\Models\RegistrationFailed;
use Illuminate\Support\Facades\DB;

class RegistrationFailedService
{
    /**
     * Backup registration to registration_failed table
     *
     * @param Registration $registration
     * @param string $reason
     * @return RegistrationFailed
     */
    public function backupFailedRegistration(Registration $registration, string $reason = 'expired_unpaid'): RegistrationFailed
    {
        return DB::transaction(function () use ($registration, $reason) {
            // Convert registration to array
            $data = $registration->toArray();
            
            // Remove id dan timestamps
            unset($data['id'], $data['created_at'], $data['updated_at']);
            
            // Add backup metadata
            $data['original_id'] = $registration->id;
            $data['failed_at'] = now();
            $data['failed_reason'] = $reason;
            
            // Create backup
            return RegistrationFailed::create($data);
        });
    }
    
    /**
     * Find failed registration by registration code
     *
     * @param string $registrationCode
     * @return RegistrationFailed|null
     */
    public function findFailedRegistration(string $registrationCode): ?RegistrationFailed
    {
        return RegistrationFailed::where('registration_code', $registrationCode)
            ->whereNull('restored_at')
            ->first();
    }
    
    /**
     * Restore failed registration to registrations table
     *
     * @param string $registrationCode
     * @param int|null $userId
     * @param string|null $note
     * @return Registration
     * @throws \Exception
     */
    public function restoreFailedRegistration(
        string $registrationCode, 
        ?int $userId = null, 
        ?string $note = null
    ): Registration {
        return DB::transaction(function () use ($registrationCode, $userId, $note) {
            $failed = $this->findFailedRegistration($registrationCode);
            
            if (!$failed) {
                throw new \Exception("Failed registration not found: {$registrationCode}");
            }
            
            // Convert to array
            $data = $failed->toArray();
            
            // Remove backup-specific fields
            unset(
                $data['id'],
                $data['original_id'],
                $data['failed_at'],
                $data['failed_reason'],
                $data['restored_at'],
                $data['restored_by'],
                $data['restore_note'],
                $data['created_at'],
                $data['updated_at']
            );
            
            // Create registration
            $registration = Registration::create($data);
            
            // Update failed record
            $failed->update([
                'restored_at' => now(),
                'restored_by' => $userId,
                'restore_note' => $note,
            ]);
            
            return $registration;
        });
    }
    
    /**
     * Get list of failed registrations with filters
     *
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFailedRegistrations(array $filters = [])
    {
        $query = RegistrationFailed::query();
        
        if (isset($filters['restored'])) {
            if ($filters['restored']) {
                $query->whereNotNull('restored_at');
            } else {
                $query->whereNull('restored_at');
            }
        }
        
        if (isset($filters['failed_reason'])) {
            $query->where('failed_reason', $filters['failed_reason']);
        }
        
        if (isset($filters['registration_code'])) {
            $query->where('registration_code', 'like', '%' . $filters['registration_code'] . '%');
        }
        
        if (isset($filters['email'])) {
            $query->where('email', 'like', '%' . $filters['email'] . '%');
        }
        
        return $query->orderBy('failed_at', 'desc')->get();
    }
}

