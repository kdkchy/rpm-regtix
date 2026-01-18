<?php

namespace App\Filament\Resources;

use App\Filament\Exports\RegistrationExporter;
use App\Filament\Resources\RegistrationResource\Pages;
use App\Helpers\CountryListHelper;
use App\Helpers\EmailSender;
use App\Models\CategoryTicketType;
use App\Models\Event;
use App\Models\Registration;
use App\Models\TicketType;
use Carbon\Carbon;
use Dom\Text;
use Filament\Actions\Exports\Enums\Contracts\ExportFormat;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Actions\ExportAction;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ExportBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Support\Facades\Auth;

class RegistrationResource extends Resource
{
    protected static ?string $model = Registration::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationGroup = 'Race Pack Management';


    private static function calculateNextRegId(): string
    {
        // Ambil reg_id terakhir (misalnya '001', '002')
        $lastRegId = Registration::orderByRaw('CAST(reg_id AS UNSIGNED) DESC')->first()->reg_id ?? '000';


        // Ambil angka dari reg_id (misalnya '001' => 1)
        $lastNumber = (int) $lastRegId;

        // Increment angka
        $next = $lastNumber + 1;

        // Format menjadi 3 digit (misalnya 2 => '002')
        return str_pad($next, 3, '0', STR_PAD_LEFT);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
            Select::make('category_ticket_type_id')
                ->label('Event Category - Ticket Type')
                ->relationship('categoryTicketType', 'id')
                ->getOptionLabelFromRecordUsing(
                    fn($record) =>
                    $record->category->event->name . ' - ' . $record->category->name . ' - ' . $record->ticketType->name
                )
                ->searchable()
                ->preload(),
            Select::make('voucher_code_id')
                ->label('Voucher Code')
                ->relationship('voucherCode', 'code')
                ->searchable()
                ->preload()
                ->placeholder('Select a Voucher Code'),
            TextInput::make('registration_code')
                ->label('Registration Code')
                ->readOnly(),
                TextInput::make('full_name')
                    ->label('Full Name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->label('Phone')
                    ->tel()
                    ->required()
                    ->regex('/^(\+62|62|0)8[1-9][0-9]{6,9}$/')
                    ->maxLength(15),
                Radio::make('gender')
                    ->label('Gender') // Label untuk field
                    ->options(Registration::GENDER) // Mengambil nama dan ID event dari model Event
                    ->required()
                    ->inline() // Menampilkan pilihan secara horizontal
                    ->reactive(),
                TextInput::make('place_of_birth')
                    ->label('Place of Birth')
                    ->required()
                    ->maxLength(255),
                DatePicker::make('dob')
                    ->label('Date of Birth')
                    ->required()
                    ->maxDate(now())
                    ->placeholder('Select Date of Birth'),
                TextInput::make('address')
                    ->label('Address')
                    ->required()
                    ->maxLength(255),
                TextInput::make('district')
                    ->label('District')
                    ->required()
                    ->maxLength(255),
                TextInput::make('province')
                    ->label('Province')
                    ->required()
                    ->maxLength(255),
                Select::make('country')
                    ->label('Country')
                    ->required()
                    ->options(CountryListHelper::get('id', true))
                    ->searchable()
                    ->placeholder('Select Country')
                    ->reactive(),
                Select::make('id_card_type')
                    ->label('ID Card Type') // Label untuk field
                    ->options(Registration::ID_CARD_TYPE) // Mengambil nama dan ID event dari model Event
                    ->required()
                    ->searchable() // Membolehkan pencarian event
                    ->placeholder('Pick an ID Card Type'),
                TextInput::make('id_card_number')
                    ->label('ID Card Number')
                    ->required()
                    ->maxLength(255),
                TextInput::make('emergency_contact_name')
                    ->label('Emergency Contact Name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('emergency_contact_phone')
                    ->label('Emergency Contact Phone')
                    ->required()
                    ->tel()
                    ->regex('/^(\+62|62|0)8[1-9][0-9]{6,9}$/')
                    ->maxLength(15),
                Select::make('nationality')
                    ->label('Nationality')
                    ->required()
                    ->options(CountryListHelper::get('id', true))
                    ->searchable()
                    ->placeholder('Select Nationality')
                    ->reactive(),
                Select::make('jersey_size')
                    ->label('Jersey Size') // Label untuk field
                    ->options(Registration::JERSEY_SIZES) // Mengambil nama dan ID event dari model Event
                    ->required()
                    ->searchable() // Membolehkan pencarian event
                    ->placeholder('Pick a Jersey Size'),
                Select::make('blood_type')
                    ->label('Blood Type') // Label untuk field
                    ->options(Registration::BLOOD_TYPE) // Mengambil nama dan ID event dari model Event
                    ->required()
                    ->searchable() // Membolehkan pencarian event
                    ->placeholder('Pick a Blood Type'),
                TextInput::make('community_name')
                    ->label('Community Name')
                    ->maxLength(255),
                TextInput::make('bib_name')
                    ->label('BIB Name')
                    ->maxLength(255),

            Hidden::make('registration_date')
                    ->default(now())
            ])
            ->disabled(function ($record) {
                $user = Auth::user();
                // Disable form jika locked (kecuali superadmin)
                if ($user->role->name === 'superadmin') {
                    return false;
                }
                
                $event = $record?->categoryTicketType?->category?->event;
                if ($event && !($event->allow_registration_edit ?? true)) {
                    return true;
                }
                
                return false;
            });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Email Status Column
                TextColumn::make('email_status')
                    ->label('Email Status')
                    ->icon(fn($record) => self::getEmailStatusIcon($record))
                    ->color(fn($record) => self::getEmailStatusColor($record))
                    ->tooltip(fn($record) => self::getEmailStatusTooltip($record))
                    ->formatStateUsing(fn($record) => self::getEmailStatusLabel($record))
                    ->sortable(false),
                // Payment Status
                TextColumn::make('payment_status')
                    ->label('Payment Status')
                    ->badge()
                    ->icon(fn($record) => $record->payment_status === 'paid' ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->formatStateUsing(fn(string $state) => $state === 'paid' ? 'Paid' : 'Unpaid')
                    ->color(fn($record) => $record->payment_status === 'paid' ? 'success' : 'danger')
                    ->searchable()
                    ->sortable(),
                // Voucher Code
                TextColumn::make('voucherCode.code')
                    ->label('Voucher Code')
                    ->sortable()
                    ->searchable(),
                // Ticket Code
                TextColumn::make('registration_code')
                    ->label('Ticket Code')
                    ->sortable()
                    ->searchable(),
                // Full Name
                TextColumn::make('full_name')
                    ->label('Full Name')
                    ->sortable()
                    ->searchable(),
                // Email
                TextColumn::make('email')
                    ->label('Email')
                    ->sortable()
                    ->searchable(),
                // Phone
                TextColumn::make('phone')
                    ->label('Phone')
                    ->sortable()
                    ->searchable(),
                // Gender
                TextColumn::make('gender')
                    ->label('Gender')
                    ->sortable()
                    ->searchable(),
                // Jersey Size
                TextColumn::make('jersey_size')
                    ->label('Jersey Size')
                    ->sortable()
                    ->searchable(),
                // BIB Name
                TextColumn::make('bib_name')
                    ->label('BIB')
                    ->sortable()
                    ->searchable(),
                // Registration Date (sebelum Created By)
                TextColumn::make('registration_date')
                    ->label('Registration Date')
                    ->sortable()
                    ->searchable()
                    ->dateTime(),
                // Created By (paling akhir - 3)
                TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->sortable()
                    ->searchable(),
                // Updated By (paling akhir - 2)
                TextColumn::make('updatedBy.name')
                    ->label('Updated By')
                    ->sortable()
                    ->searchable(),
                // Edit Lock Status (paling akhir - 1)
                TextColumn::make('edit_lock_status')
                    ->label('Edit Lock')
                    ->badge()
                    ->formatStateUsing(function ($record) {
                        $event = $record->categoryTicketType?->category?->event;
                        if ($event) {
                            return $event->allow_registration_edit ?? true ? 'Allowed' : 'Locked';
                        }
                        return 'Unknown';
                    })
                    ->color(function ($record) {
                        $event = $record->categoryTicketType?->category?->event;
                        if ($event) {
                            return ($event->allow_registration_edit ?? true) ? 'success' : 'danger';
                        }
                        return 'gray';
                    })
                    ->icon(function ($record) {
                        $event = $record->categoryTicketType?->category?->event;
                        if ($event) {
                            return ($event->allow_registration_edit ?? true) ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed';
                        }
                        return 'heroicon-o-question-mark-circle';
                    })
                    ->sortable(false),
                // Status RPC (paling akhir)
                TextColumn::make('is_validated')
                    ->badge()
                    ->icon(fn($record) => $record->is_validated ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->formatStateUsing(fn($state) => $state ? 'Validated' : 'Not Validated')
                    ->color(fn($record) => $record->is_validated ? 'success' : 'danger')
                    ->label('Status RPC')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
            SelectFilter::make('event_id')
                ->label('Event')
                ->options(function () {
                    $query = Event::query();
                /** @var \App\Models\User $user */
                $user = Auth::user();
                if (Auth::user()->role->name !== 'superadmin') {
                    // Ambil semua event id yang dimiliki user
                    $eventIds =  $user->events()->pluck('events.id')->toArray();
                    $query->whereIn('id', $eventIds);
                }

                return $query->pluck('name', 'id')->toArray();
                })
                ->query(function ($query, $state) {
                    $query->when($state['value'] != null, function ($query) use ($state) {
                        $query->whereHas('categoryTicketType.category.event', function ($q) use ($state) {
                        // $state biasanya value langsung id event
                        $q->where('id', $state['value']);
                        });
                    });
                }),
            SelectFilter::make('category_ticket_type_id')
                ->label('Event Category - Ticket Type')
                ->options(function () {
                    // Ambil event_id dari berbagai sumber
                    $eventId = null;
                    
                    // Cara 1: Dari request tableFilters (saat filter di-submit)
                    $allRequest = request()->all();
                    if (isset($allRequest['tableFilters']['event_id']['value'])) {
                        $eventId = $allRequest['tableFilters']['event_id']['value'];
                    }
                    
                    // Cara 2: Dari query string (format berbeda)
                    if (!$eventId && request()->has('tableFilters')) {
                        $tableFilters = request('tableFilters');
                        if (is_array($tableFilters) && isset($tableFilters['event_id']['value'])) {
                            $eventId = $tableFilters['event_id']['value'];
                        }
                    }
                    
                    // Cara 3: Dari session - Filament menyimpan dengan key: filament.resources.{resource}.table.filters
                    if (!$eventId) {
                        // Coba key yang lebih spesifik berdasarkan resource name
                        $sessionKey = 'filament.resources.registrations.table.filters';
                        $sessionFilters = session()->get($sessionKey, []);
                        if (isset($sessionFilters['event_id']['value'])) {
                            $eventId = $sessionFilters['event_id']['value'];
                        } elseif (isset($sessionFilters['event_id']) && !is_array($sessionFilters['event_id'])) {
                            // Format alternatif: langsung value tanpa nested array
                            $eventId = $sessionFilters['event_id'];
                        }
                    }
                    
                    // Cara 4: Dari session - coba semua kemungkinan key yang mengandung filters
                    if (!$eventId) {
                        $allSession = session()->all();
                        foreach ($allSession as $key => $value) {
                            if ((str_contains($key, 'filters') || str_contains($key, 'table')) && is_array($value)) {
                                if (isset($value['event_id']['value'])) {
                                    $eventId = $value['event_id']['value'];
                                    break;
                                }
                                // Cek nested array
                                if (isset($value['event_id']) && is_array($value['event_id']) && isset($value['event_id']['value'])) {
                                    $eventId = $value['event_id']['value'];
                                    break;
                                }
                                // Format alternatif: langsung value
                                if (isset($value['event_id']) && !is_array($value['event_id'])) {
                                    $eventId = $value['event_id'];
                                    break;
                                }
                            }
                        }
                    }
                    
                    // Cara 5: Dari URL query parameter langsung
                    if (!$eventId) {
                        $eventId = request()->query('tableFilters.event_id.value');
                    }
                    
                    // Debug: Uncomment untuk debug jika masih bermasalah
                    // \Log::info('Filter Category-Ticket Type Options', [
                    //     'eventId' => $eventId,
                    //     'hasTableFilters' => request()->has('tableFilters'),
                    //     'tableFilters' => request('tableFilters'),
                    //     'sessionKey' => 'filament.resources.registrations.table.filters',
                    //     'sessionValue' => session()->get('filament.resources.registrations.table.filters', 'not found'),
                    // ]);
                    
                    /** @var \App\Models\User $user */
                    $user = Auth::user();
                    $query = CategoryTicketType::query()->with(['category.event', 'ticketType']);

                    // Jika event dipilih, filter berdasarkan event tersebut
                    if ($eventId) {
                        $query->whereHas('category.event', function ($q) use ($eventId) {
                            $q->where('id', $eventId);
                        });
                    } else {
                        // Jika event belum dipilih, tampilkan semua (atau filter berdasarkan user events)
                        if ($user->role->name !== 'superadmin') {
                            $eventIds = $user->events()->pluck('events.id')->toArray();
                            if (!empty($eventIds)) {
                                $query->whereHas('category.event', function ($q) use ($eventIds) {
                                    $q->whereIn('id', $eventIds);
                                });
                            } else {
                                return [];
                            }
                        }
                    }

                    // Jika bukan superadmin, pastikan event tersebut dimiliki user
                    if ($user->role->name !== 'superadmin' && $eventId) {
                        $eventIds = $user->events()->pluck('events.id')->toArray();
                        if (!in_array($eventId, $eventIds)) {
                            return [];
                        }
                    }

                    return $query->get()->mapWithKeys(function ($record) {
                        return [
                            $record->id => $record->category->event->name
                                . ' - ' . $record->category->name
                                . ' - ' . $record->ticketType->name,
                        ];
                    })->toArray();
                })
                ->query(function ($query, $state) {
                    $query->when($state['value'] != null, function ($query) use ($state) {
                        $query->where('category_ticket_type_id', $state['value']);
                    });
                }),
            SelectFilter::make('payment_status')
                ->label('Payment Status')
                ->options([
                    'paid' => 'Paid',
                    'unpaid' => 'Unpaid',
                ]),
            SelectFilter::make('email_status')
                ->label('Email Status')
                ->options([
                    'delivered' => 'Delivered',
                    'sent' => 'Sent',
                    'bounced' => 'Bounced',
                    'hardBounce' => 'Hard Bounce',
                    'softBounce' => 'Soft Bounce',
                    'invalid' => 'Invalid',
                    'error' => 'Error',
                    'no_status' => 'No Status',
                ])
                ->query(function (Builder $query, array $data) {
                    if (empty($data['value'])) {
                        return $query;
                    }

                    $status = $data['value'];
                    
                    // Cek apakah ada relasi latestEmailLog, jika tidak skip filter
                    try {
                        if ($status === 'no_status') {
                            return $query->whereDoesntHave('latestEmailLog');
                        }

                        return $query->whereHas('latestEmailLog', function ($q) use ($status) {
                            $q->where('status', $status);
                        });
                    } catch (\Exception $e) {
                        // Jika relasi tidak ada, return query tanpa filter
                        return $query;
                    }
                }),
                SelectFilter::make('is_validated')
                    ->label('Status RPC')
                    ->columns(1)
                    ->options([
                        '1' => 'Validated',
                        '0' => 'Not Validated',
                    ]),

            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->actions([
                Action::make('send_email')
                    ->label('Send')
                    ->icon('heroicon-o-envelope')
                    ->modalWidth('sm')
                    ->visible(fn($record) => $record->payment_status === 'paid')
                    ->form([
                        TextInput::make('email_address')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->default(fn ($record) => $record->email),
                        TextInput::make('cc_email_address')
                            ->label('Email Address')
                            ->email()
                            ->maxLength(255)
                        ])
                    ->action(function($record, array $data){
                        $email = new EmailSender();
                        $subject = $record->event->name . ' - Your Print-At-Home Tickets have arrived! - Do Not Reply';
                        $template = file_get_contents(resource_path('email/templates/e-ticket.html'));
                        $email->sendEmail($record, $subject, $template, $data['email_address'], $data['cc_email_address']);

                        Notification::make()
                            ->title('Email sent successfully!')
                            ->success()
                            ->send();

                    })
                    ->modalSubmitActionLabel('Send Email'),
                Action::make('print')
                    ->label('Print')
                    ->icon('heroicon-m-printer')
                    ->url(function ($record) {
                        return route('registration.print', $record->id);
                    })
                    ->openUrlInNewTab()
                    ->visible(function ($record) {
                        return $record->is_validated;
                    }),
            ], position: ActionsPosition::BeforeColumns)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Delete Selected (Unpaid will be backed up)')
                        ->modalHeading('Delete Selected Registrations')
                        ->modalDescription('Unpaid registrations will be automatically backed up to registration_backups table before deletion. Paid registrations will also be backed up with a warning. Are you sure?')
                        ->modalSubmitActionLabel('Yes, Delete')
                        ->visible(fn(): bool => Auth::user()->role->name === 'superadmin'),
                ExportBulkAction::make()
                    ->visible(fn(): bool => in_array(Auth::user()->role->name, ['superadmin', 'admin']))
                    ->label('Export Selected')
                    ->exporter(RegistrationExporter::class)
                    ->filename('registrations-' . now()->format('Y-m-d-his'))
                    ->color('success'),
            ])
            ])
            ->persistFiltersInSession(true)
            ->defaultSort('registration_date', 'desc')
            ->emptyStateHeading('No Registrations Found')
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(50);
    }

    /**
     * Get email status icon based on latest email log.
     */
    private static function getEmailStatusIcon($record): ?string
    {
        try {
            $emailLog = $record->latestEmailLog ?? null;
        } catch (RelationNotFoundException $e) {
            $emailLog = null;
        } catch (\Exception $e) {
            $emailLog = null;
        }
        
        if (!$emailLog) {
            return 'heroicon-o-envelope';
        }

        return match($emailLog->status) {
            'delivered' => 'heroicon-o-check-circle',
            'sent' => 'heroicon-o-clock',
            'bounced', 'hardBounce', 'invalid' => 'heroicon-o-x-circle',
            'softBounce' => 'heroicon-o-exclamation-triangle',
            'error' => 'heroicon-o-exclamation-circle',
            default => 'heroicon-o-envelope',
        };
    }

    /**
     * Get email status color based on latest email log.
     */
    private static function getEmailStatusColor($record): string
    {
        try {
            $emailLog = $record->latestEmailLog ?? null;
        } catch (RelationNotFoundException $e) {
            $emailLog = null;
        } catch (\Exception $e) {
            $emailLog = null;
        }
        
        if (!$emailLog) {
            return 'gray';
        }

        return match($emailLog->status) {
            'delivered' => 'success',
            'sent' => 'warning',
            'bounced', 'hardBounce', 'invalid', 'error' => 'danger',
            'softBounce' => 'warning',
            default => 'gray',
        };
    }

    /**
     * Get email status label.
     */
    private static function getEmailStatusLabel($record): string
    {
        try {
            $emailLog = $record->latestEmailLog ?? null;
        } catch (RelationNotFoundException $e) {
            $emailLog = null;
        } catch (\Exception $e) {
            $emailLog = null;
        }
        
        if (!$emailLog) {
            return 'No Status';
        }

        return match($emailLog->status) {
            'delivered' => 'Delivered',
            'sent' => 'Sent',
            'bounced' => 'Bounced',
            'hardBounce' => 'Hard Bounce',
            'softBounce' => 'Soft Bounce',
            'invalid' => 'Invalid',
            'error' => 'Error',
            default => 'Unknown',
        };
    }

    /**
     * Get email status tooltip with details.
     */
    private static function getEmailStatusTooltip($record): ?string
    {
        try {
            $emailLog = $record->latestEmailLog ?? null;
        } catch (RelationNotFoundException $e) {
            $emailLog = null;
        } catch (\Exception $e) {
            $emailLog = null;
        }
        
        if (!$emailLog) {
            return 'Belum ada log email';
        }

        $tooltip = "Status: " . self::getEmailStatusLabel($record) . "\n";
        
        if ($emailLog->sent_at) {
            $tooltip .= "Sent: " . Carbon::parse($emailLog->sent_at)->format('d M Y H:i') . "\n";
        }
        
        if ($emailLog->delivered_at) {
            $tooltip .= "Delivered: " . Carbon::parse($emailLog->delivered_at)->format('d M Y H:i') . "\n";
        }
        
        if ($emailLog->bounced_at) {
            $tooltip .= "Bounced: " . Carbon::parse($emailLog->bounced_at)->format('d M Y H:i') . "\n";
        }
        
        if ($emailLog->error_message) {
            $tooltip .= "Error: {$emailLog->error_message}";
        }

        return $tooltip;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRegistrations::route('/'),
            'create' => Pages\CreateRegistration::route('/create'),
            'edit' => Pages\EditRegistration::route('/{record}/edit'),
            'view' => Pages\ViewRegistration::route('/{record}'),
        ];
    }

    public static function canEdit($record): bool
    {
        $user = Auth::user();
        
        // Superadmin selalu bisa edit
        if ($user->role->name === 'superadmin') {
            return true;
        }
        
        // Cek flag allow_registration_edit dari event terkait
        $event = $record->categoryTicketType?->category?->event;
        if ($event) {
            return $event->allow_registration_edit ?? true;
        }
        
        // Default allow jika event tidak ditemukan
        return true;
    }

    public static function canViewAny(): bool
    {
        // Semua user yang login bisa view list
        return Auth::check();
    }

    public static function canView($record): bool
    {
        // Semua user yang bisa view list, bisa view detail
        return true;
    }

    public static function canDelete($record): bool
    {
        // Hanya superadmin yang bisa delete
        return Auth::user()?->role?->name === 'superadmin';
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = parent::getEloquentQuery();
        
        // Note: latestEmailLog relationship is not loaded to avoid errors if it doesn't exist
        // Each method that uses latestEmailLog will handle it safely with try-catch

        // Admin bisa lihat semua
        if ($user->role->name === 'superadmin') {
            return $query;
        }

        // User biasa hanya lihat event tertentu
        $eventIds = $user->events()->pluck('events.id')->toArray();

        return $query->whereHas('categoryTicketType.category.event', function ($query) use ($eventIds) {
            $query->whereIn('events.id', $eventIds);
        });
    }
}
