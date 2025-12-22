<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Registration;
use App\Models\Event;
use Filament\Forms\Components\Select;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class NewReport extends Page
{
    protected static string $view = 'filament.pages.new-report';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?int $navigationSort = 6;
    protected static ?string $navigationGroup = 'Race Pack Management';
    protected static ?string $navigationLabel = 'New Event Report';

    public array $chartData = [];
    public int $totalRegistrations = 0;
    public float $totalRevenue = 0;
    public ?int $selectedEvent = null;

    public array $globalStats = [];
    public array $perTicketStats = [];
    public array $communityRanks = [];
    public array $cityRanks = [];
    public array $jerseyStats = [];

    public string $reportGeneratedAt;

    public function mount(): void
    {
        $this->reportGeneratedAt = now()->timezone(config('app.timezone', 'Asia/Makassar'))->format('l, d F Y : H.i T');

        $user = Auth::user();
        $events = $user->role->name === 'superadmin'
            ? Event::pluck('name', 'id')
            : $user->events()->pluck('events.name', 'events.id');

        $this->selectedEvent = $events->keys()->first();

        $this->updateReport();
    }

    public function updateReport(): void
    {
        if (! $this->selectedEvent) {
            $this->resetReportData();
            return;
        }

        $event = Event::find($this->selectedEvent);
        if (! $event) {
            $this->resetReportData();
            return;
        }

        /** @var Collection<int, \App\Models\Registration> $registrations */
        $registrations = Registration::with([
                'categoryTicketType.category.event',
                'categoryTicketType.ticketType',
                'voucherCode.voucher',
            ])
            ->whereHas('categoryTicketType.category', fn ($q) =>
                $q->where('event_id', $event->id)
            )
            ->where('payment_status', 'paid')
            ->get();

        // Global totals
        $this->totalRegistrations = $registrations->count();
        $this->totalRevenue = $registrations->sum(fn ($r) => $this->resolvePrice($r));

        $this->globalStats = [
            'total_participants' => $this->totalRegistrations,
            'total_revenue' => $this->totalRevenue,
            'gender' => [
                'male' => $registrations->where('gender', 'Male')->count(),
                'female' => $registrations->where('gender', 'Female')->count(),
            ],
            'nationality' => [
                'indonesian' => $registrations->where('nationality', 'Indonesia')->count(),
                'foreigner' => $registrations->filter(fn ($r) => $r->nationality && $r->nationality !== 'Indonesia')->count(),
            ],
        ];

        // Group by Category - TicketType for chart & per ticket stats
        $grouped = $registrations->groupBy(function ($r) {
            $category = $r->categoryTicketType?->category?->name ?? '-';
            $ticket = $r->categoryTicketType?->ticketType?->name ?? '-';
            $eventName = $r->categoryTicketType?->category?->event?->name;

            return trim(($eventName ? $eventName . ': ' : '') . $category . ' - ' . $ticket);
        });

        $this->chartData = [
            'labels' => $grouped->keys()->toArray(),
            'values' => $grouped->map(fn ($group) => $group->count())->values()->toArray(),
            'revenues' => $grouped->map(
                fn ($group) => $group->sum(fn ($r) => $this->resolvePrice($r))
            )->values()->toArray(),
        ];

        $this->perTicketStats = $grouped->map(function (Collection $group, string $label) {
            return [
                'label' => $label,
                'participants' => $group->count(),
                'revenue' => $group->sum(fn ($r) => $this->resolvePrice($r)),
                'gender' => [
                    'male' => $group->where('gender', 'Male')->count(),
                    'female' => $group->where('gender', 'Female')->count(),
                ],
                'jersey_sizes' => $group->groupBy('jersey_size')->map->count()->sortKeys()->toArray(),
            ];
        })->values()->toArray();

        // Community rank (global)
        $this->communityRanks = $registrations
            ->filter(fn ($r) => filled($r->community_name))
            ->groupBy('community_name')
            ->map->count()
            ->sortDesc()
            ->map(fn ($count, $name) => [
                'name' => $name,
                'count' => $count,
            ])
            ->values()
            ->toArray();

        // City / Regency rank (global) - use district + province if available
        $this->cityRanks = $registrations
            ->map(function ($r) {
                $parts = array_filter([
                    $r->district ?: null,
                    $r->province ?: null,
                ]);

                return count($parts) ? implode(', ', $parts) : ($r->country ?: null);
            })
            ->filter()
            ->groupBy(fn ($location) => $location)
            ->map->count()
            ->sortDesc()
            ->map(fn ($count, $location) => [
                'location' => $location,
                'count' => $count,
            ])
            ->values()
            ->toArray();

        // Jersey stats global, grouped by distance / category and size
        $this->jerseyStats = $registrations
            ->filter(fn ($r) => filled($r->jersey_size))
            ->groupBy(function ($r) {
                // assume category name roughly corresponds to distance / race type
                return $r->categoryTicketType?->category?->name ?? 'Unknown';
            })
            ->map(function (Collection $group, string $categoryName) {
                return [
                    'category' => $categoryName,
                    'sizes' => $group->groupBy('jersey_size')->map->count()->sortKeys()->toArray(),
                ];
            })
            ->values()
            ->toArray();

        $this->dispatch('chartUpdated', [
            'chartData' => $this->chartData ?? ['labels' => [], 'values' => [], 'revenues' => []],
        ]);
    }

    protected function getFormSchema(): array
    {
        $user = Auth::user();
        $events = $user->role->name === 'superadmin'
            ? Event::pluck('name', 'id')
            : $user->events()->pluck('events.name', 'events.id');

        return [
            Select::make('selectedEvent')
                ->label('Select Event')
                ->default($events->keys()->first())
                ->options($events)
                ->searchable()
                ->reactive()
                ->afterStateUpdated(fn () => $this->updateReport())
                ->extraAttributes(['style' => 'max-width:350px;']),
        ];
    }

    protected function resetReportData(): void
    {
        $this->chartData = [];
        $this->totalRegistrations = 0;
        $this->totalRevenue = 0;
        $this->globalStats = [];
        $this->perTicketStats = [];
        $this->communityRanks = [];
        $this->cityRanks = [];
        $this->jerseyStats = [];
    }

    protected function resolvePrice(Registration $registration): float|int
    {
        return $registration->voucherCode?->voucher?->final_price
            ?? $registration->categoryTicketType->price
            ?? 0;
    }
}


