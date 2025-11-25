<?php

namespace App\Filament\Widgets;

use App\Models\Event;
use App\Models\Registration;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class RegistrationWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();

        $allowedEventIds = $user->role->name === 'superadmin'
            ? Event::where('status', 'OPEN')->pluck('events.id')->toArray() // semua event
            : $user->events()->where('status', 'OPEN')->pluck('events.id')->toArray();
        // Ambil semua registrasi paid
        $registrations = Registration::with([
            'categoryTicketType.category',
            'categoryTicketType.ticketType',
            'categoryTicketType.category.event'
            ])
            ->where('payment_status', 'paid')
            ->when($allowedEventIds, fn($q) => 
                $q->whereHas('categoryTicketType.category', fn($q) => $q->whereIn('event_id', $allowedEventIds))
            )
            ->get();

        // Group by "Category - TicketType"
        $data = $registrations
            ->groupBy(fn($r) => $r->categoryTicketType->category->name . ' - ' . $r->categoryTicketType->ticketType->name);

        $stats = [];

        $registrations->groupBy(fn($r) => $r->categoryTicketType->category->event->name)
        ->each(function ($eventGroup, $eventName) use (&$stats) {

            // 1️⃣ Stat total per event
            $totalCount = $eventGroup->count();
            $totalRevenue = $eventGroup->sum(fn($r) => $r->categoryTicketType->price ?? 0);

            $stats[] = Stat::make("{$eventName}", "Rp " . number_format($totalRevenue, 0, ',', '.'))
                ->description($totalCount . ' Participants')
                ->descriptionIcon('heroicon-m-users', IconPosition::Before)
                ->chart([0, 10, 20, 30, 40,])
                ->color('primary');

            // 2️⃣ Stats per category-ticket type
            $eventGroup->groupBy(fn($r) =>
                $r->categoryTicketType->category->name . ' - ' . $r->categoryTicketType->ticketType->name
            )->each(function ($group, $key) use (&$stats, $eventName) {
                $count = $group->count();
                $revenue = $group->sum(fn($r) => $r->categoryTicketType->price ?? 0);

                $stats[] = Stat::make("{$eventName}: {$key}", "Rp " . number_format($revenue, 0, ',', '.'))
                    ->description($count . ' Participants')
                    ->descriptionIcon('heroicon-m-users', IconPosition::Before)
                    ->chart([0, 10, 20, 30, 40])
                    ->color('success');
            });
        });
        return $stats;
    }
}

