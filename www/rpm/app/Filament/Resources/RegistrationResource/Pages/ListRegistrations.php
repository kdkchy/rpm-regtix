<?php

namespace App\Filament\Resources\RegistrationResource\Pages;

use App\Filament\Exports\RegistrationExporter;
use App\Filament\Resources\RegistrationResource;
use App\Models\Category;
use App\Models\Event;
use App\Models\Registration;
use App\Models\TicketType;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListRegistrations extends ListRecords
{
    protected static string $resource = RegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_bib')
                ->label('Generate BIB Number')
                ->icon('heroicon-o-cog')
                ->color('success')
                ->modalWidth('sm')
                ->form([
                    Select::make('event_id')
                        ->label('Event')                    
                        ->placeholder('Select Event')
                        ->options(function () {
                            $user = Auth::user();
                            
                            if ($user->role->name === 'superadmin') {
                                return Event::pluck('name', 'id')->toArray();
                            }

                            return $user->events()->pluck('events.name', 'events.id')->toArray();
                        })
                        ->reactive()
                        ->afterStateUpdated(function($set, $state){
                            $set('category_id', null);
                        })
                        ->required(),
                    Select::make('category_id')
                        ->label('Category')
                        ->placeholder('Select Category')
                        ->options(function ($get) {
                            $eventId = $get('event_id');

                            if (!$eventId) {
                                return [];
                            }

                            return Category::where('event_id', $eventId)
                                ->pluck('name', 'id');
                        })
                        ->required(),
                    TextInput::make('prefix')
                        ->label('BIB Prefix')
                        ->maxLength(5),
                    TextInput::make('length')
                        ->label('BIB Length')
                        ->numeric()
                        ->minValue(3)
                        ->default(3)
                        ->required()
                        
                ])
                ->action(function(array $data){
                    $registrations = Registration::where('payment_status', 'paid')
                        ->whereHas('categoryTicketType', fn($q) => $q->where('category_id', $data['category_id']))
                        ->orderBy('created_at')
                        ->orderBy('id')
                        ->get();

                    $prefix = $data['prefix'];
                    $length = $data['length'];
                    $length = max($length, strlen((string)$registrations->count()));

                    foreach($registrations as $index => $registration){
                        
                        $bib =  $prefix . str_pad($index + 1, $length, '0', STR_PAD_LEFT);
                        $registration->update([
                            'reg_id' => $bib,
                        ]);
                    }
                    
                    Notification::make()
                        ->title('BIB Numbers Generated')
                        ->body(count($registrations). " registrations updated successfully")
                        ->success()
                        ->send();
                    

                })->modalSubmitActionLabel('Generate BIB')
                ,
            ExportAction::make('Export')
                ->visible(fn(): bool => in_array(Auth::user()->role->name, ['superadmin', 'admin']))
                ->exporter(RegistrationExporter::class)
                ->icon('heroicon-o-arrow-down-tray')
                ->fileName('registration-' . now()->format('Y-m-d-his'))
                ->color('success'),
            Actions\CreateAction::make(),
        ];
    }

    public function tableQuery(Builder $query)
    {
        return $query->when(request()->has('event_id'), function ($query) {
            return $query->where('event_id', request('event_id'));
        });
    }
}
