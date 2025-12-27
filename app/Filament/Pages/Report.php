<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\RegistrationRevenueChart;
use Filament\Pages\Page;
use App\Models\Registration;
use App\Models\Event;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Auth;

class Report extends Page
{
    protected static string $view = 'filament.pages.report';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationGroup = 'Race Pack Management';

    public array $chartData = [];
    public int $totalRegistrations = 0;
    public float $totalRevenue = 0;
    public ?int $selectedEvent = null;

    public array $jerseyByCategory = [];
    
    public string $reportGeneratedAt;
    
    public array $globalStats = [];
    public array $genderNationalityTable = [];
    public array $jerseyTable = [];
    public array $communityRanks = [];
    public array $cityRanks = [];




    public function updateReport(): void
    {
        if (! $this->selectedEvent) {
            $this->chartData = [];
            $this->totalRegistrations = 0;
            $this->totalRevenue = 0;
            $this->globalStats = [];
            $this->genderNationalityTable = [];
            $this->jerseyTable = [];
            $this->jerseyByCategory = [];
            $this->communityRanks = [];
            $this->cityRanks = [];
            return;
        }

        $event = Event::find($this->selectedEvent);
        if (! $event) return;

        $registrations = Registration::with([
            'categoryTicketType.category', 
            'categoryTicketType.ticketType',
            'voucherCode.voucher'
        ])
            ->whereHas('categoryTicketType.category', fn($q) =>
                $q->where('event_id', $event->id)
            )
            ->where(function ($q) {
                $q->where('payment_status', 'paid');
            })
            ->get();

        // Filter out registrations with incomplete relations first for consistency
        $validRegistrations = $registrations->filter(fn($r) => 
            $r->categoryTicketType?->category && $r->categoryTicketType?->ticketType
        );

        $this->totalRegistrations = $validRegistrations->count();
        
        // Calculate revenue: use gross_amount (actual payment) if available, otherwise use voucher final_price or categoryTicketType price
        $this->totalRevenue = $validRegistrations->sum(function ($r) {
            // Prioritize gross_amount (actual payment received)
            if ($r->gross_amount !== null && $r->gross_amount > 0) {
                return (float) $r->gross_amount;
            }
            // Fallback to voucher final_price or categoryTicketType price
            return $r->voucherCode?->voucher?->final_price ?? $r->categoryTicketType?->price ?? 0;
        });

        // Group by category & ticket type
        $data = $validRegistrations
            ->groupBy(fn($r) => $r->categoryTicketType->category->name . ' - ' . $r->categoryTicketType->ticketType->name);

        $this->chartData = [
            'labels' => $data->keys()->toArray(),
            'values' => $data->map(fn($group) => count($group))->values()->toArray(),
            'revenues' => $data->map(fn($group) => $group->sum(function ($r) {
                // Prioritize gross_amount (actual payment received)
                if ($r->gross_amount !== null && $r->gross_amount > 0) {
                    return (float) $r->gross_amount;
                }
                // Fallback to voucher final_price or categoryTicketType price
                return $r->voucherCode?->voucher?->final_price ?? $r->categoryTicketType?->price ?? 0;
            }))->values()->toArray(),
        ];

        $sizeOrder = ['XS','S','M','L','XL','XXL'];

        // Calculate global stats
        $this->globalStats = [
            'total_participants' => $this->totalRegistrations,
            'total_revenue' => $this->totalRevenue,
        ];

        // Calculate gender & nationality table
        $this->genderNationalityTable = $data->map(function ($group, $key) {
            $male = $group->where('gender', 'Male')->count();
            $female = $group->where('gender', 'Female')->count();
            $foreigner = $group->where('nationality', '!=', 'Indonesia')->count();
            
            return [
                'ticketType' => $key,
                'male' => $male,
                'female' => $female,
                'foreigner' => $foreigner,
            ];
        })->values()->toArray();

        // Add total row - use validRegistrations for consistency
        $this->genderNationalityTable[] = [
            'ticketType' => 'Total',
            'male' => $validRegistrations->where('gender', 'Male')->count(),
            'female' => $validRegistrations->where('gender', 'Female')->count(),
            'foreigner' => $validRegistrations->where('nationality', '!=', 'Indonesia')->count(),
        ];

        // Calculate jersey by category (for backward compatibility)
        $this->jerseyByCategory = $validRegistrations
            ->whereNotNull('jersey_size')
            ->groupBy(fn($r) => $r->categoryTicketType->category->name)
            ->map(function ($group) use ($sizeOrder) {

                // hitung jumlah per size
                $sizes = $group->groupBy('jersey_size')
                            ->map(fn($g) => $g->count())
                            ->toArray(); // harus array dulu untuk uksort

                // custom sort berdasarkan prefix
                uksort($sizes, function($a, $b) use ($sizeOrder) {
                    $indexA = array_search(
                        collect($sizeOrder)->first(fn($p) => str_starts_with($a, $p)),
                        $sizeOrder,
                        true
                    );
                    $indexB = array_search(
                        collect($sizeOrder)->first(fn($p) => str_starts_with($b, $p)),
                        $sizeOrder,
                        true
                    );

                    $indexA = $indexA === false ? 999 : $indexA;
                    $indexB = $indexB === false ? 999 : $indexB;

                    return $indexA <=> $indexB;
                });

                return $sizes;
            })->toArray();

        // Calculate jersey table (grouped by category & ticket type)
        $jerseyByCategoryTicketType = $validRegistrations
            ->whereNotNull('jersey_size')
            ->groupBy(fn($r) => $r->categoryTicketType->category->name . ' - ' . $r->categoryTicketType->ticketType->name)
            ->map(function ($group) use ($sizeOrder) {
                $sizes = $group->groupBy('jersey_size')
                    ->map(fn($g) => $g->count())
                    ->toArray();

                uksort($sizes, function($a, $b) use ($sizeOrder) {
                    $indexA = array_search(
                        collect($sizeOrder)->first(fn($p) => str_starts_with($a, $p)),
                        $sizeOrder,
                        true
                    );
                    $indexB = array_search(
                        collect($sizeOrder)->first(fn($p) => str_starts_with($b, $p)),
                        $sizeOrder,
                        true
                    );

                    $indexA = $indexA === false ? 999 : $indexA;
                    $indexB = $indexB === false ? 999 : $indexB;

                    return $indexA <=> $indexB;
                });

                return $sizes;
            })
            ->toArray();

        // Get all unique ticket types for jersey table
        $ticketTypes = $data->keys()->toArray();
        
        // Build jersey table data
        $allSizes = collect($jerseyByCategoryTicketType)
            ->flatMap(fn($sizes) => array_keys($sizes))
            ->unique()
            ->toArray();
        
        usort($allSizes, function($a, $b) use ($sizeOrder) {
            $indexA = array_search(
                collect($sizeOrder)->first(fn($p) => str_starts_with($a, $p)),
                $sizeOrder,
                true
            );
            $indexB = array_search(
                collect($sizeOrder)->first(fn($p) => str_starts_with($b, $p)),
                $sizeOrder,
                true
            );
            $indexA = $indexA === false ? 999 : $indexA;
            $indexB = $indexB === false ? 999 : $indexB;
            return $indexA <=> $indexB;
        });

        $jerseyTableData = [];
        foreach ($allSizes as $size) {
            $row = ['size' => $size];
            $rowTotal = 0;
            foreach ($ticketTypes as $ticketType) {
                $count = isset($jerseyByCategoryTicketType[$ticketType][$size]) 
                    ? $jerseyByCategoryTicketType[$ticketType][$size] 
                    : 0;
                $row[$ticketType] = $count;
                $rowTotal += $count;
            }
            $row['totals'] = $rowTotal;
            $jerseyTableData[] = $row;
        }

        // Add total row
        $totalRow = ['size' => 'Total'];
        $grandTotal = 0;
        foreach ($ticketTypes as $ticketType) {
            $total = isset($jerseyByCategoryTicketType[$ticketType]) 
                ? array_sum($jerseyByCategoryTicketType[$ticketType]) 
                : 0;
            $totalRow[$ticketType] = $total;
            $grandTotal += $total;
        }
        $totalRow['totals'] = $grandTotal;
        $jerseyTableData[] = $totalRow;

        $this->jerseyTable = [
            'ticketTypes' => $ticketTypes,
            'data' => $jerseyTableData,
        ];

        // Calculate community ranks - use validRegistrations for consistency
        $this->communityRanks = $validRegistrations
            ->whereNotNull('community_name')
            ->groupBy('community_name')
            ->map(fn($group, $name) => [
                'name' => $name,
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->values()
            ->toArray();

        // Calculate city ranks - use validRegistrations for consistency
        $this->cityRanks = $validRegistrations
            ->whereNotNull('district')
            ->groupBy('district')
            ->map(fn($group, $location) => [
                'location' => $location,
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->values()
            ->toArray();

       $this->dispatch('chartUpdated', [
            'chartData' => $this->chartData ?? ['labels'=>[], 'values'=>[], 'revenues'=>[]],
            'jerseySizes' => $this->jerseyByCategory
        ]);
    }


    public function mount(): void
    {
        $this->reportGeneratedAt = now()->format('d M Y H:i:s');
        
        $user = Auth::user();
        $events = $user->role->name === 'superadmin'
            ? Event::pluck('name', 'id')
            : $user->events()->pluck('events.name', 'events.id');
        $this->selectedEvent =  $events->keys()->first();

        $this->updateReport();
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
                ->afterStateUpdated(fn() => $this->updateReport())
                ->extraAttributes(['style' => 'max-width:350px;']),
        ];
    }

    public function printXml($eventId)
    {
        $event = Event::findOrFail($eventId);
        
        // Authorization check: verify user has access to this event
        $user = Auth::user();
        $authorizedEventIds = $user->role->name === 'superadmin'
            ? Event::pluck('id')->toArray()
            : $user->events()->pluck('events.id')->toArray();
        
        if (!in_array($event->id, $authorizedEventIds)) {
            abort(403, 'Anda tidak memiliki akses ke event ini.');
        }
        
        // Get all paid registrations for this event
        $registrations = Registration::with([
            'categoryTicketType.category', 
            'categoryTicketType.ticketType',
            'voucherCode.voucher'
        ])
            ->whereHas('categoryTicketType.category', fn($q) =>
                $q->where('event_id', $event->id)
            )
            ->where('payment_status', 'paid')
            ->get();

        // Filter out registrations with incomplete relations first for consistency
        $validRegistrations = $registrations->filter(fn($r) => 
            $r->categoryTicketType?->category && $r->categoryTicketType?->ticketType
        );

        // Calculate global stats
        $totalRegistrations = $validRegistrations->count();
        
        // Calculate revenue: use gross_amount (actual payment) if available, otherwise use voucher final_price or categoryTicketType price
        $totalRevenue = $validRegistrations->sum(function ($r) {
            // Prioritize gross_amount (actual payment received)
            if ($r->gross_amount !== null && $r->gross_amount > 0) {
                return (float) $r->gross_amount;
            }
            // Fallback to voucher final_price or categoryTicketType price
            return $r->voucherCode?->voucher?->final_price ?? $r->categoryTicketType?->price ?? 0;
        });

        $globalStats = [
            'total_participants' => $totalRegistrations,
            'total_revenue' => $totalRevenue,
        ];

        // Calculate chart data (same as updateReport)
        $data = $validRegistrations
            ->groupBy(fn($r) => $r->categoryTicketType->category->name . ' - ' . $r->categoryTicketType->ticketType->name);

        $chartData = [
            'labels' => $data->keys()->toArray(),
            'values' => $data->map(fn($group) => count($group))->values()->toArray(),
            'revenues' => $data->map(fn($group) => $group->sum(function ($r) {
                // Prioritize gross_amount (actual payment received)
                if ($r->gross_amount !== null && $r->gross_amount > 0) {
                    return (float) $r->gross_amount;
                }
                // Fallback to voucher final_price or categoryTicketType price
                return $r->voucherCode?->voucher?->final_price ?? $r->categoryTicketType?->price ?? 0;
            }))->values()->toArray(),
        ];

        // Calculate gender & nationality table
        $genderNationalityTable = $data->map(function ($group, $key) {
            $male = $group->where('gender', 'Male')->count();
            $female = $group->where('gender', 'Female')->count();
            $foreigner = $group->where('nationality', '!=', 'Indonesia')->count();
            
            return [
                'ticketType' => $key,
                'male' => $male,
                'female' => $female,
                'foreigner' => $foreigner,
            ];
        })->values()->toArray();

        // Add total row - use validRegistrations for consistency
        $genderNationalityTable[] = [
            'ticketType' => 'Total',
            'male' => $validRegistrations->where('gender', 'Male')->count(),
            'female' => $validRegistrations->where('gender', 'Female')->count(),
            'foreigner' => $validRegistrations->where('nationality', '!=', 'Indonesia')->count(),
        ];

        // Calculate jersey table
        $sizeOrder = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
        $jerseyByCategory = $validRegistrations
            ->whereNotNull('jersey_size')
            ->groupBy(fn($r) => $r->categoryTicketType->category->name . ' - ' . $r->categoryTicketType->ticketType->name)
            ->map(function ($group) use ($sizeOrder) {
                $sizes = $group->groupBy('jersey_size')
                    ->map(fn($g) => $g->count())
                    ->toArray();

                uksort($sizes, function($a, $b) use ($sizeOrder) {
                    $indexA = array_search(
                        collect($sizeOrder)->first(fn($p) => str_starts_with($a, $p)),
                        $sizeOrder,
                        true
                    );
                    $indexB = array_search(
                        collect($sizeOrder)->first(fn($p) => str_starts_with($b, $p)),
                        $sizeOrder,
                        true
                    );

                    $indexA = $indexA === false ? 999 : $indexA;
                    $indexB = $indexB === false ? 999 : $indexB;

                    return $indexA <=> $indexB;
                });

                return $sizes;
            })
            ->toArray();

        // Get all unique ticket types for jersey table
        $ticketTypes = $data->keys()->toArray();
        
        // Build jersey table data
        $allSizes = collect($jerseyByCategory)
            ->flatMap(fn($sizes) => array_keys($sizes))
            ->unique()
            ->toArray();
        
        usort($allSizes, function($a, $b) use ($sizeOrder) {
            $indexA = array_search(
                collect($sizeOrder)->first(fn($p) => str_starts_with($a, $p)),
                $sizeOrder,
                true
            );
            $indexB = array_search(
                collect($sizeOrder)->first(fn($p) => str_starts_with($b, $p)),
                $sizeOrder,
                true
            );
            $indexA = $indexA === false ? 999 : $indexA;
            $indexB = $indexB === false ? 999 : $indexB;
            return $indexA <=> $indexB;
        });

        $jerseyTableData = [];
        foreach ($allSizes as $size) {
            $row = ['size' => $size];
            $rowTotal = 0;
            foreach ($ticketTypes as $ticketType) {
                $count = isset($jerseyByCategory[$ticketType][$size]) 
                    ? $jerseyByCategory[$ticketType][$size] 
                    : 0;
                $row[$ticketType] = $count;
                $rowTotal += $count;
            }
            $row['totals'] = $rowTotal;
            $jerseyTableData[] = $row;
        }

        // Add total row
        $totalRow = ['size' => 'Total'];
        $grandTotal = 0;
        foreach ($ticketTypes as $ticketType) {
            $total = isset($jerseyByCategory[$ticketType]) 
                ? array_sum($jerseyByCategory[$ticketType]) 
                : 0;
            $totalRow[$ticketType] = $total;
            $grandTotal += $total;
        }
        $totalRow['totals'] = $grandTotal;
        $jerseyTableData[] = $totalRow;

        $jerseyTable = [
            'ticketTypes' => $ticketTypes,
            'data' => $jerseyTableData,
        ];

        // Calculate community ranks - use validRegistrations for consistency
        $communityRanks = $validRegistrations
            ->whereNotNull('community_name')
            ->groupBy('community_name')
            ->map(fn($group, $name) => [
                'name' => $name,
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->values()
            ->toArray();

        // Calculate city ranks - use validRegistrations for consistency
        $cityRanks = $validRegistrations
            ->whereNotNull('district')
            ->groupBy('district')
            ->map(fn($group, $location) => [
                'location' => $location,
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->values()
            ->toArray();

        return view('filament.pages.report-xml', [
            'event' => $event,
            'reportGeneratedAt' => now()->format('d M Y H:i:s'),
            'globalStats' => $globalStats,
            'chartData' => $chartData,
            'genderNationalityTable' => $genderNationalityTable,
            'jerseyTable' => $jerseyTable,
            'communityRanks' => $communityRanks,
            'cityRanks' => $cityRanks,
        ]);
    }
}

