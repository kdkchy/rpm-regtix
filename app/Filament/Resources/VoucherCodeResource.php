<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VoucherCodeResource\Pages;
use App\Filament\Resources\VoucherCodeResource\RelationManagers;
use App\Models\VoucherCode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class VoucherCodeResource extends Resource
{
    protected static ?string $model = VoucherCode::class;
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationGroup = 'Event Management';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
        ->columns([
            TextColumn::make('voucher.name')->label('Voucher Name')->searchable(),
            TextColumn::make('voucher.categoryTicketType.category.event.name')->searchable()
                ->label('Event Name'),
            TextColumn::make('voucher.categoryTicketType.category.name')->searchable()
                ->label('Category Name'),
            TextColumn::make('voucher.categoryTicketType.ticketType.name')->searchable()
                ->label('Ticket Type Name'),
                TextColumn::make('code')->label('Voucher Code')->searchable(),
                TextColumn::make('used')
                    ->label('Used')
                    ->getStateUsing(function ($record) {
                        return $record->registrations()->count();
                    })   // single-use: 1 jika sudah dipakai
                    ->badge()
                    ->color(fn($state) => $state > 0 ? 'success' : 'danger'),

                TextColumn::make('remaining')
                    ->label('Remaining')
                    ->getStateUsing(function ($record) {
                        $used = $record->registrations()->count(); // jumlah registrasi terkait
                        return ($record->voucher->is_multiple_use ? $record->voucher->max_usage : 1) - $used;
                    })
                    ->badge()
                    ->color(fn($state) => $state > 0 ? 'success' : 'danger'),
                // Kolom lain yang dibutuhkan
            ])
            ->filters([
                // Filter berdasarkan voucher_id jika ada

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
            'index' => Pages\ListVoucherCodes::route('/'),
            'create' => Pages\CreateVoucherCode::route('/create'),
            // 'edit' => Pages\EditVoucherCode::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = parent::getEloquentQuery();

        // Filter berdasarkan user event
        if ($user->role->name !== 'superadmin') {

            $userIds = $user->events()->pluck('events.id')->toArray();

            $query->whereHas('voucher.categoryTicketType.category.event', function ($q) use ($userIds) {
                $q->whereIn('id', $userIds);
            });
        }

        // Filter berdasarkan voucher_id dari URL query param
        if (request()->has('voucher_id')) {
            $query->where('voucher_id', request('voucher_id'));
        }

        return $query;
    }
}
