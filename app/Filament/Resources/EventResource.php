<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Pages;
use App\Filament\Resources\EventResource\RelationManagers;
use App\Models\Event;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationGroup = 'Event Management';

    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?int $navigationSort = 2;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Event Details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->label('Event Name')
                            ->maxLength(255),
                        DateTimePicker::make('start_date')
                            ->required()
                            ->label('Event Start Date')
                    ->placeholder('Select Start Date'),
                        DateTimePicker::make('end_date')
                            ->required()
                            ->label('Event End Date')
                    ->placeholder('Select End Date'),
                Select::make('status')
                    ->label('Event Status') // Label untuk field
                    ->options(Event::STATUS) // Mengambil nama dan ID event dari model Event
                    ->required()
                    ->searchable() // Membolehkan pencarian event
                    ->placeholder('Pick a status'),
                Select::make('size')
                    ->label('Event Size') // Label untuk field
                    ->options(Event::SIZE) // Mengambil nama dan ID event dari model Event
                    ->required()
                    ->searchable() // Membolehkan pencarian event
                    ->placeholder('Pick size'),
                        TextInput::make('location')
                            ->required()
                            ->label('Location')
                            ->maxLength(255),
                        Textarea::make('location_gmaps_url')
                            ->label('Maps URL')
                            ->rows(3),
                        DatePicker::make('registration_start_date')
                            ->label('Registration Start Date')
                    ->placeholder('Select Registration Start Date'),
                        DatePicker::make('registration_end_date')
                            ->label('Registration End Date')
                    ->placeholder('Select Registration End Date'),
                        Textarea::make('description')
                            ->label('Description')
                            ->label('Description'),
                DatePicker::make('rpc_start_date')
                    ->label('RPC Start Date'),
                DatePicker::make('rpc_end_date')
                    ->label('RPC Start Date'),
                        TextInput::make('rpc_collection_times')
                            ->label('RPC Collection Times')
                            ->maxLength(255),
                        TextInput::make('rpc_collection_location')
                            ->label('RPC Collection Location')
                            ->maxLength(255),
                        Textarea::make('rpc_collection_gmaps_url')
                            ->label('RPC Collection Maps URL')
                            ->rows(3),
                        TextInput::make('event_url')
                            ->label('Event URL')
                            ->maxLength(255),
                        TextInput::make('ig_url')
                            ->label('Instagram URL')
                            ->maxLength(255),
                        TextInput::make('fb_url')
                            ->label('Facebook URL')
                            ->maxLength(225),
                        TextInput::make('contact_email')
                            ->label('Contact Email')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('contact_phone')
                            ->label('Contact Phone')
                            ->tel()
                            ->maxLength(255),
                        TextInput::make('event_owner')
                            ->label('Event Owner')
                            ->maxLength(255),
                        TextInput::make('event_organizer')
                            ->label('Event Organizer')
                            ->maxLength(255),
                        TextInput::make('code_prefix')
                            ->label('Code Prefix')
                            ->maxLength(255),
                        FileUpload::make('event_logo')
                            // ->preserveFilenames()
                            ->label('Event Logo')
                            ->image()
                            ->disk('public')
                            ->directory(fn($record) => $record?->id
                                ? "image/event/logo/{$record->id}"
                                : "image/event/logo/")
                            ->visibility('public')
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                null,
                                '16:9',
                                '4:3',
                                '1:1',
                            ]),
                        FileUpload::make('event_banner')
                            // ->preserveFilenames()
                            ->label('Event Banner')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->visibility('public')
                            ->directory(fn($record) => $record?->id
                                ? "image/event/banner/{$record->id}"
                                : "image/event/banner/")
                            ->imageEditorAspectRatios([
                                null,
                                '16:9',
                                '4:3',
                                '1:1',
                            ]),
                        FileUpload::make('jersey_size_image')
                            // ->preserveFilenames()
                            ->label('Jersey Size Chart')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->visibility('public')
                            ->directory(fn($record) => $record?->id
                                ? "image/event/jersey-size/{$record->id}"
                                : "image/event/jersey-size/")
                            ->imageEditorAspectRatios([
                                null,
                                '16:9',
                                '4:3',
                                '1:1',
                            ]),
                        Section::make('Event Slides')
                            ->schema([
                                Repeater::make('slides')
                                    ->relationship('slides')
                                    ->schema([
                                        FileUpload::make('image_path')
                                        ->label('Event Slides')
                                        ->image()
                                        ->imageEditor()
                                        ->disk('public')
                                        ->visibility('public')
                                        ->directory('image/event/slides')
                                        ->imageEditorAspectRatios([
                                            null,
                                            '16:9',
                                            '4:3',
                                            '1:1',
                                        ]),
                                        TextInput::make('caption')
                                            ->label('Caption')
                                            ->maxLength(255),
                                        TextInput::make('order')
                                            ->label('Order')
                                            ->numeric()
                                            ->maxLength(255),
                                    ])
                                ->collapsible()
                            ])
                    ])
                    ->collapsible()
                    ->collapsed(false),
                Section::make('Event Categories')
                    ->schema([
                        Repeater::make('categories')
                            ->relationship()
                            ->columns(2)
                            ->live()
                            ->deleteAction(
                                fn(Action $action) => $action->requiresConfirmation()
                            )
                            ->schema([
                                Group::make([
                                    TextInput::make('name')
                                        ->required()
                                        ->label('Category Name')
                                        ->maxLength(255),
                                    TextInput::make('distance')
                                        ->required()
                                        ->numeric()
                                        ->label('Distance')
                                        ->step(1)
                                        ->maxLength(255)
                                        ->suffix('KM'),
                                ])->columnSpan(1),

                                Group::make([
                                    Repeater::make('categoryTicketTypes')
                                        ->relationship()
                                        ->live()
                                        ->label('Ticket Types')
                                        ->schema([
                                            Select::make('ticket_type_id')
                                                ->label('Ticket Type')
                                                ->options(function () {
                                                    return \App\Models\TicketType::all()->pluck('name', 'id');
                                                })
                                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                                ->required(),
                                            TextInput::make('price')
                                                ->required()
                                                ->label('Price')
                                                ->numeric()
                                                ->minValue(0)
                                                ->maxValue(10000000)
                                                ->mask(RawJs::make('$money($input)'))
                                                ->stripCharacters(',')
                                                ->prefix('Rp ')
                                                ->reactive(),
                                            TextInput::make('quota')
                                                ->numeric()
                                                ->required(),
                                            DatePicker::make('valid_from')
                                                ->label('Valid From')
                                                ->placeholder('Select Date')
                                        ])
                                        ->columns(1)
                                ])

                            ])
                            ->columns(2),
                    ])
                    ->collapsible()
                    ->collapsed(true)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('index')
                    ->rowIndex()
                    ->label('#'),
                TextColumn::make('name')
                    ->label('Event Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('start_date')
                    ->label('Start Date')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('End Date')
                    ->searchable()
                    ->sortable(),
            TextColumn::make('status')
                ->label('Status')
                ->searchable()
                ->sortable(),
            TextColumn::make('size')
                ->label('Size')
                ->searchable()
                ->sortable(),
                TextColumn::make('location')
                    ->label('Location')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code_prefix')
                    ->label('Code Prefix')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
                Filter::make('name')
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->label('Event Name')
                            ->placeholder('Search Event Name'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['name'], function ($query, $name) {
                            $query->where('name', 'like', "%{$name}%");
                        });
                    }),
                Filter::make('start_date')
                    ->form([
                        DatePicker::make('start_date')
                            ->label('Start Date')
                            ->placeholder('Select Start Date'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['start_date'], function ($query, $date) {
                            $query->whereDate('start_date', '>=', $date); // Pastikan field database benar
                        });
                    }),
                Filter::make('end_date')
                    ->form([
                        DatePicker::make('end_date')
                            ->label('End Date')
                            ->placeholder('Select End Date'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['end_date'], function ($query, $date) {
                            $query->whereDate('end_date', '<=', $date); // Pastikan field database benar
                        });
                    }),

            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
    public static function canViewAny(): bool
    {
        return in_array(Auth::user()->role->name, ['superadmin', 'admin']);
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Admin lihat semua
        if ($user->role->name === 'superadmin') {
            return parent::getEloquentQuery();
        }

        $eventIds = $user->events()->pluck('events.id')->toArray();
        // User biasa filter voucher berdasarkan event_id user
        return parent::getEloquentQuery()->whereIn('id', $eventIds);
    }
}
