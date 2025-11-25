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



    public function updateReport(): void
    {
        if (! $this->selectedEvent) {
            $this->chartData = [];
            $this->totalRegistrations = 0;
            $this->totalRevenue = 0;
            return;
        }

        $event = Event::find($this->selectedEvent);
        if (! $event) return;

        $registrations = Registration::with(['categoryTicketType.category', 'categoryTicketType.ticketType'])
            ->whereHas('categoryTicketType.category', fn($q) =>
                $q->where('event_id', $event->id)
            )
            ->where(function ($q) {
                $q->where('payment_status', 'paid');
            })
            ->get();


        $this->totalRegistrations = $registrations->count();
        $this->totalRevenue = $registrations->sum(function ($r) {
            return $r->categoryTicketType->price ?? 0;
        });

        // Group by category & ticket type
        $data = $registrations
            ->groupBy(fn($r) => $r->categoryTicketType->category->name . ' - ' . $r->categoryTicketType->ticketType->name);

        $this->chartData = [
            'labels' => $data->keys()->toArray(),
            'values' => $data->map(fn($group) => count($group))->values()->toArray(),
            'revenues' => $data->map(fn($group) => $group->sum(fn($r) => $r->categoryTicketType->price ?? 0))->values()->toArray(),
        ];

       $this->dispatch('chartUpdated', [
            'chartData' => $this->chartData ?? ['labels'=>[], 'values'=>[], 'revenues'=>[]]
        ]);
    }


    public function mount(): void
    {
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
}

