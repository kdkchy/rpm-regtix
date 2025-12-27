<?php

namespace App\Filament\Resources\RegistrationResource\Pages;

use App\Filament\Exports\RegistrationExporter;
use App\Filament\Resources\RegistrationResource;
use App\Models\Category;
use App\Models\Event;
use App\Models\Registration;
use App\Models\TicketType;
use App\Services\EmailAnnouncementService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
            Action::make('email_blast')
                ->label('Email Blast')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->visible(fn(): bool => in_array(Auth::user()->role->name, ['superadmin', 'admin']))
                ->modalWidth('lg')
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
                        ->required()
                        ->helperText(function ($get) {
                            $eventId = $get('event_id');
                            if (!$eventId) {
                                return 'Select an event to see recipient count';
                            }
                            $count = Registration::where('payment_status', 'paid')
                                ->whereHas('categoryTicketType.category.event', function ($query) use ($eventId) {
                                    $query->where('events.id', $eventId);
                                })
                                ->count();
                            return "This email will be sent to {$count} paid registrations";
                        }),
                    TextInput::make('subject')
                        ->label('Email Subject')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Important Announcement - {{event_name}}'),
                    Textarea::make('html_template')
                        ->label('HTML Template')
                        ->required()
                        ->rows(10)
                        ->columnSpanFull()
                        ->helperText('You can use variables like {{name}}, {{email}}, {{event_name}}, {{registration_code}}, etc.')
                        ->placeholder('<html><body><h1>Hello {{name}},</h1><p>This is an important announcement for {{event_name}}.</p></body></html>'),
                ])
                ->action(function (array $data, EmailAnnouncementService $service) {
                    $user = Auth::user();
                    
                    // Validate user has access to this event
                    if ($user->role->name !== 'superadmin') {
                        $userEventIds = $user->events()->pluck('events.id')->toArray();
                        if (!in_array($data['event_id'], $userEventIds)) {
                            Notification::make()
                                ->title('Access Denied')
                                ->body('You do not have access to this event.')
                                ->danger()
                                ->send();
                            return;
                        }
                    }

                    try {
                        $announcement = $service->sendEmailBlast(
                            $data['event_id'],
                            $data['subject'],
                            $data['html_template'],
                            $user->id
                        );

                        $message = "Email blast sent successfully!\n";
                        $message .= "Total recipients: {$announcement->total_recipients}\n";
                        $message .= "Sent: {$announcement->sent_count}\n";
                        if ($announcement->failed_count > 0) {
                            $message .= "Failed: {$announcement->failed_count}";
                        }

                        Notification::make()
                            ->title('Email Blast Completed')
                            ->body($message)
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Email Blast Failed')
                            ->body('An error occurred: ' . $e->getMessage())
                            ->danger()
                            ->send();
                        
                        \Log::error('Email blast failed', [
                            'user_id' => $user->id,
                            'event_id' => $data['event_id'],
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                })
                ->modalSubmitActionLabel('Send Email Blast')
                ->modalDescription('Send an important announcement to all paid registrations in the selected event.')
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
