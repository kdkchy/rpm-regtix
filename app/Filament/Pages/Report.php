<?php

namespace App\Filament\Pages;

use App\Models\Event;
use App\Models\Registration;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class Report extends Page
{
    protected static string $view = 'filament.pages.report';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationGroup = 'Race Pack Management';
    
    protected static ?string $title = null;

    private const JERSEY_SIZE_ORDER = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];

    // Public properties for view
    public array $chartData = [];
    public int $totalRegistrations = 0;
    public float $totalRevenue = 0;
    public ?int $selectedEvent = null;
    public array $globalStats = [];
    public array $perTicketStats = [];
    public array $communityRanks = [];
    public array $cityRanks = [];
    public array $jerseyStats = [];
    public array $jerseyTable = [];
    public array $genderNationalityTable = [];
    public string $reportGeneratedAt;

    public function mount(): void
    {
        $this->reportGeneratedAt = now()
            ->timezone(config('app.timezone', 'Asia/Makassar'))
            ->format('l, d F Y : H.i T');

        $this->selectedEvent = $this->getAvailableEvents()->keys()->first();
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

        $registrations = $this->getRegistrations($event);
        $grouped = $this->groupByTicketType($registrations);

        $this->calculateGlobalStats($registrations);
        $this->calculateChartData($grouped);
        $this->calculatePerTicketStats($grouped);
        $this->calculateCommunityRanks($registrations);
        $this->calculateCityRanks($registrations);
        $this->calculateJerseyStats($registrations);
        $this->calculateJerseyTable($registrations, $grouped);
        $this->calculateGenderNationalityTable($grouped);
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('selectedEvent')
                ->label('Select Event')
                ->options($this->getAvailableEvents())
                ->default($this->getAvailableEvents()->keys()->first())
                ->searchable()
                ->reactive()
                ->afterStateUpdated(fn () => $this->updateReport())
                ->placeholder('Choose an event...')
                ->extraAttributes(['class' => 'w-full']),
        ];
    }

    // ==================== Data Retrieval ====================

    protected function getAvailableEvents(): Collection
    {
        $user = Auth::user();

        if (!$user) {
            return collect([]);
        }

        return $user?->role?->name === 'superadmin'
            ? Event::orderBy('start_date', 'desc')->pluck('name', 'id')
            : $user->events()->orderBy('events.start_date', 'desc')->pluck('events.name', 'events.id');
    }

    protected function getRegistrations(Event $event): Collection
    {
        return Registration::with([
                'categoryTicketType.category.event',
                'categoryTicketType.ticketType',
                'voucherCode.voucher',
            ])
            ->whereHas('categoryTicketType.category', fn ($q) =>
                $q->where('event_id', $event->id)
            )
            ->where('payment_status', 'paid')
            ->get();
    }

    protected function groupByTicketType(Collection $registrations): Collection
    {
        $grouped = $registrations->groupBy(function ($r) {
            $category = $r->categoryTicketType?->category?->name ?? '-';
            $ticket = $r->categoryTicketType?->ticketType?->name ?? '-';
            $eventName = $r->categoryTicketType?->category?->event?->name;

            return trim(($eventName ? $eventName . ': ' : '') . $category . ' - ' . $ticket);
        });

        // Sort by category first, then ticket type
        return $grouped->sortKeysUsing(function ($a, $b) {
            return $this->compareTicketTypes($a, $b);
        });
    }

    protected function compareTicketTypes(string $a, string $b): int
    {
        // Extract category and ticket type from labels
        // Format: "Event: Category - TicketType" or "Category - TicketType"
        $partsA = $this->parseTicketLabel($a);
        $partsB = $this->parseTicketLabel($b);

        // First compare by category (5K, 10K, 200M, etc.)
        $categoryCompare = $this->compareCategories($partsA['category'], $partsB['category']);
        if ($categoryCompare !== 0) {
            return $categoryCompare;
        }

        // If same category, compare by ticket type (Early, Regular, Kids, etc.)
        return $this->compareTicketTypeNames($partsA['ticket'], $partsB['ticket']);
    }

    protected function parseTicketLabel(string $label): array
    {
        // Remove event name if present
        $label = preg_replace('/^[^:]+:\s*/', '', $label);
        
        // Split by " - "
        $parts = explode(' - ', $label, 2);
        
        return [
            'category' => $parts[0] ?? '',
            'ticket' => $parts[1] ?? '',
        ];
    }

    protected function compareCategories(string $a, string $b): int
    {
        // Extract numeric value from category (5K, 10K, 200M, etc.)
        preg_match('/(\d+)/', $a, $matchA);
        preg_match('/(\d+)/', $b, $matchB);
        
        $numA = $matchA[1] ?? 9999;
        $numB = $matchB[1] ?? 9999;
        
        // Compare numbers first
        if ($numA != $numB) {
            return $numA <=> $numB;
        }
        
        // If same number, compare by unit (K vs M)
        $unitA = strtoupper(substr($a, -1));
        $unitB = strtoupper(substr($b, -1));
        
        // M comes after K
        if ($unitA === 'M' && $unitB === 'K') return 1;
        if ($unitA === 'K' && $unitB === 'M') return -1;
        
        return strcasecmp($a, $b);
    }

    protected function compareTicketTypeNames(string $a, string $b): int
    {
        $order = ['Early', 'Regular', 'Kids', 'Late'];
        
        $indexA = array_search($a, $order);
        $indexB = array_search($b, $order);
        
        if ($indexA === false) $indexA = 999;
        if ($indexB === false) $indexB = 999;
        
        return $indexA <=> $indexB;
    }

    // ==================== Statistics Calculation ====================

    protected function calculateGlobalStats(Collection $registrations): void
    {
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
                'foreigner' => $registrations->filter(fn ($r) =>
                    $r->nationality && $r->nationality !== 'Indonesia'
                )->count(),
            ],
        ];
    }

    protected function calculateChartData(Collection $grouped): void
    {
        $this->chartData = [
            'labels' => $grouped->keys()->toArray(),
            'values' => $grouped->map(fn ($group) => $group->count())->values()->toArray(),
            'revenues' => $grouped->map(fn ($group) =>
                $group->sum(fn ($r) => $this->resolvePrice($r))
            )->values()->toArray(),
        ];
    }

    protected function calculatePerTicketStats(Collection $grouped): void
    {
        $this->perTicketStats = $grouped->map(function (Collection $group, string $label) {
            return [
                'label' => $label,
                'participants' => $group->count(),
                'revenue' => $group->sum(fn ($r) => $this->resolvePrice($r)),
                'gender' => [
                    'male' => $group->where('gender', 'Male')->count(),
                    'female' => $group->where('gender', 'Female')->count(),
                ],
                'jersey_sizes' => $this->sortJerseySizes(
                    $group->groupBy('jersey_size')->map->count()->toArray()
                ),
            ];
        })->values()->toArray();
    }

    protected function calculateCommunityRanks(Collection $registrations): void
    {
        $this->communityRanks = $registrations
            ->filter(fn ($r) => filled($r->community_name))
            ->groupBy('community_name')
            ->map->count()
            ->sortDesc()
            ->take(50)
            ->map(fn ($count, $name) => ['name' => $name, 'count' => $count])
            ->values()
            ->toArray();
    }

    protected function calculateCityRanks(Collection $registrations): void
    {
        $this->cityRanks = $registrations
            ->map(fn ($r) => $this->formatLocation($r))
            ->filter()
            ->groupBy(fn ($location) => $location)
            ->map->count()
            ->sortDesc()
            ->map(fn ($count, $location) => ['location' => $location, 'count' => $count])
            ->values()
            ->toArray();
    }

    protected function calculateJerseyStats(Collection $registrations): void
    {
        $this->jerseyStats = $registrations
            ->filter(fn ($r) => filled($r->jersey_size))
            ->groupBy(fn ($r) => $r->categoryTicketType?->category?->name ?? 'Unknown')
            ->map(function (Collection $group, string $categoryName) {
                return [
                    'category' => $categoryName,
                    'sizes' => $this->sortJerseySizes(
                        $group->groupBy('jersey_size')->map->count()->toArray()
                    ),
                ];
            })
            ->values()
            ->toArray();
    }

    protected function calculateJerseyTable(Collection $registrations, Collection $grouped): void
    {
        // Get all unique jersey sizes from registrations
        $allSizes = $registrations
            ->filter(fn ($r) => filled($r->jersey_size))
            ->pluck('jersey_size')
            ->unique()
            ->sortBy(fn ($size) => $this->getJerseySizeIndex($size))
            ->values()
            ->toArray();

        // Get all ticket type labels (columns)
        $ticketTypes = $grouped->keys()->toArray();

        // If no sizes or ticket types, return empty
        if (empty($allSizes) || empty($ticketTypes)) {
            $this->jerseyTable = [
                'sizes' => [],
                'ticketTypes' => [],
                'data' => [],
            ];
            return;
        }

        // Build table structure: rows = sizes, columns = ticket types
        $table = [];
        foreach ($allSizes as $size) {
            $row = ['size' => $size, 'totals' => 0];
            
            foreach ($ticketTypes as $ticketType) {
                $count = $grouped->has($ticketType)
                    ? $grouped[$ticketType]->filter(fn ($r) => $r->jersey_size === $size)->count()
                    : 0;
                
                $row[$ticketType] = $count;
                $row['totals'] += $count;
            }
            
            $table[] = $row;
        }

        // Add totals row
        $totalsRow = ['size' => 'Total', 'totals' => 0];
        foreach ($ticketTypes as $ticketType) {
            $total = $grouped->has($ticketType)
                ? $grouped[$ticketType]->filter(fn ($r) => filled($r->jersey_size))->count()
                : 0;
            $totalsRow[$ticketType] = $total;
            $totalsRow['totals'] += $total;
        }
        $table[] = $totalsRow;

        $this->jerseyTable = [
            'sizes' => $allSizes,
            'ticketTypes' => $ticketTypes,
            'data' => $table,
        ];
    }

    // ==================== Helper Methods ====================

    protected function formatLocation(Registration $registration): ?string
    {
        $parts = array_filter([
            $registration->district ?: null,
            $registration->province ?: null,
        ]);

        return count($parts) ? implode(', ', $parts) : ($registration->country ?: null);
    }

    protected function resolvePrice(Registration $registration): float|int
    {
        return $registration->voucherCode?->voucher?->final_price
            ?? $registration->categoryTicketType?->price
            ?? 0;
    }

    protected function sortJerseySizes(array $sizes): array
    {
        uksort($sizes, fn ($a, $b) =>
            $this->getJerseySizeIndex($a) <=> $this->getJerseySizeIndex($b)
        );

        return $sizes;
    }

    protected function getJerseySizeIndex(string $size): int
    {
        // Check if it's a Kids size
        $isKids = stripos($size, 'kids') !== false;
        
        // Extract base size (XS, S, M, L, XL, XXL)
        $prefix = collect(self::JERSEY_SIZE_ORDER)->first(fn ($p) => str_starts_with($size, $p));
        $baseIndex = array_search($prefix, self::JERSEY_SIZE_ORDER, true);

        // If base size not found, put at the end
        if ($baseIndex === false) {
            return 999;
        }

        // If it's Kids size, add 6 to put it after regular sizes
        // Regular: 0-5 (XS, S, M, L, XL, XXL)
        // Kids: 6-11 (XS Kids, S Kids, M Kids, L Kids, XL Kids, XXL Kids)
        return $isKids ? $baseIndex + 6 : $baseIndex;
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
        $this->jerseyTable = [];
        $this->genderNationalityTable = [];
    }

    public function printXml($eventId)
    {
        $event = Event::findOrFail($eventId);
        $this->selectedEvent = $eventId;
        $this->reportGeneratedAt = now()
            ->timezone(config('app.timezone', 'Asia/Makassar'))
            ->format('l, d F Y : H.i T');
        
        $registrations = $this->getRegistrations($event);
        $grouped = $this->groupByTicketType($registrations);

        $this->calculateGlobalStats($registrations);
        $this->calculateChartData($grouped);
        $this->calculateJerseyTable($registrations, $grouped);
        $this->calculateGenderNationalityTable($grouped);
        $this->calculateCommunityRanks($registrations);
        $this->calculateCityRanks($registrations);

        $data = [
            'event' => $event,
            'reportGeneratedAt' => $this->reportGeneratedAt,
            'globalStats' => $this->globalStats,
            'chartData' => $this->chartData,
            'jerseyTable' => $this->jerseyTable,
            'genderNationalityTable' => $this->genderNationalityTable,
            'communityRanks' => $this->communityRanks,
            'cityRanks' => $this->cityRanks,
        ];

        return response()->view('filament.pages.report-xml', $data)
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }

    protected function calculateGenderNationalityTable(Collection $grouped): void
    {
        $table = [];
        $totals = ['male' => 0, 'female' => 0, 'foreigner' => 0];

        // Use same order as chartData labels (already sorted)
        $ticketTypes = $grouped->keys()->toArray();

        foreach ($ticketTypes as $ticketType) {
            if (!$grouped->has($ticketType)) {
                continue;
            }

            $group = $grouped[$ticketType];
            $male = $group->where('gender', 'Male')->count();
            $female = $group->where('gender', 'Female')->count();
            $foreigner = $group->filter(fn ($r) =>
                $r->nationality && $r->nationality !== 'Indonesia'
            )->count();

            $table[] = [
                'ticketType' => $ticketType,
                'male' => $male,
                'female' => $female,
                'foreigner' => $foreigner,
            ];

            $totals['male'] += $male;
            $totals['female'] += $female;
            $totals['foreigner'] += $foreigner;
        }

        // Add totals row
        $table[] = [
            'ticketType' => 'Total',
            'male' => $totals['male'],
            'female' => $totals['female'],
            'foreigner' => $totals['foreigner'],
        ];

        $this->genderNationalityTable = $table;
    }
}
