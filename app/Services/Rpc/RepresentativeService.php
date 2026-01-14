<?php

namespace App\Services\Rpc;

use Illuminate\Support\Facades\Cache;

class RepresentativeService
{
    /**
     * Cache TTL in seconds (24 hours)
     */
    const CACHE_TTL = 86400;

    /**
     * Store representative data in cache
     *
     * @param int $operatorId
     * @param array $data
     * @return string Representative ID
     */
    public function store(int $operatorId, array $data): string
    {
        $representativeId = uniqid('rep_', true);
        $cacheKey = $this->getRepresentativeKey($operatorId, $representativeId);
        
        Cache::put($cacheKey, $data, self::CACHE_TTL);
        
        return $representativeId;
    }

    /**
     * Get representative data from cache
     *
     * @param int $operatorId
     * @param string $representativeId
     * @return array|null
     */
    public function get(int $operatorId, string $representativeId): ?array
    {
        $cacheKey = $this->getRepresentativeKey($operatorId, $representativeId);
        
        return Cache::get($cacheKey);
    }

    /**
     * Assign participants to representative
     *
     * @param int $operatorId
     * @param string $representativeId
     * @param array $participantIds
     * @return void
     */
    public function assignParticipants(int $operatorId, string $representativeId, array $participantIds): void
    {
        $cacheKey = $this->getParticipantsKey($operatorId, $representativeId);
        
        Cache::put($cacheKey, $participantIds, self::CACHE_TTL);
    }

    /**
     * Get representative with assigned participants
     *
     * @param int $operatorId
     * @param string $representativeId
     * @return array|null
     */
    public function getWithParticipants(int $operatorId, string $representativeId): ?array
    {
        $representative = $this->get($operatorId, $representativeId);
        
        if (!$representative) {
            return null;
        }
        
        $participantsKey = $this->getParticipantsKey($operatorId, $representativeId);
        $participantIds = Cache::get($participantsKey, []);
        
        return [
            'representative' => $representative,
            'participant_ids' => $participantIds,
        ];
    }

    /**
     * Delete representative and its participants from cache
     *
     * @param int $operatorId
     * @param string $representativeId
     * @return void
     */
    public function delete(int $operatorId, string $representativeId): void
    {
        $representativeKey = $this->getRepresentativeKey($operatorId, $representativeId);
        $participantsKey = $this->getParticipantsKey($operatorId, $representativeId);
        
        Cache::forget($representativeKey);
        Cache::forget($participantsKey);
    }

    /**
     * Get cache key for representative
     *
     * @param int $operatorId
     * @param string $representativeId
     * @return string
     */
    private function getRepresentativeKey(int $operatorId, string $representativeId): string
    {
        return "rpc:representative:{$operatorId}:{$representativeId}";
    }

    /**
     * Get cache key for participants assignment
     *
     * @param int $operatorId
     * @param string $representativeId
     * @return string
     */
    private function getParticipantsKey(int $operatorId, string $representativeId): string
    {
        return "rpc:representative:{$operatorId}:{$representativeId}:participants";
    }
}

