<?php

namespace App\Filament\Exports;

use App\Models\Registration;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class RegistrationExporter extends Exporter
{
    protected static ?string $model = Registration::class;

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with([
            'categoryTicketType.category',
            'categoryTicketType.ticketType',
            'voucherCode.voucher',
        ]);
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('registration_code')->label('Kode Tiket'),
            ExportColumn::make('category_name')
                ->label('Kategory')
                ->getStateUsing(function (Registration $record) {
                    return $record->categoryTicketType?->category?->name ?? '';
                }),
            ExportColumn::make('ticket_type_name')
                ->label('Tipe Tiket')
                ->getStateUsing(function (Registration $record) {
                    return $record->categoryTicketType?->ticketType?->name ?? '';
                }),
            ExportColumn::make('full_name')->label('Nama'),
            ExportColumn::make('email')->label('Email'),
            ExportColumn::make('phone')->label('Nomor HP'),
            ExportColumn::make('gender')->label('Jenis Kelamin'),
            ExportColumn::make('place_of_birth')->label('Tempat Lahir'),
            ExportColumn::make('dob')->label('Tanggal Lahir'),
            ExportColumn::make('address')->label('Alamat'),
            ExportColumn::make('district')->label('Kecamatan'),
            ExportColumn::make('province')->label('Provinsi'),
            ExportColumn::make('country')->label('Negara'),
            ExportColumn::make('id_card_type')->label('Tipe Kartu Identitas'),
            ExportColumn::make('id_card_number')->label('Nomor Kartu Identitas'),
            ExportColumn::make('emergency_contact_name')->label('Nama Kontak Darurat'),
            ExportColumn::make('emergency_contact_phone')->label('Nomor Kontak Darurat'),
            ExportColumn::make('blood_type')->label('Golongan Darah'),
            ExportColumn::make('nationality')->label('Kewarganegaraan'),
            ExportColumn::make('jersey_size')->label('Size Jersey'),
            ExportColumn::make('community_name')->label('Komunitas'),
            ExportColumn::make('bib_name')->label('Nama BIB'),
            ExportColumn::make('reg_id')->label('Nomer Registrasi'),
            ExportColumn::make('registration_date')->label('Tanggal Registrasi'),
            ExportColumn::make('gross_amount_calc')
                ->label('Gross Amount')
                ->getStateUsing(function(Registration $record){
                    try {
                        if ($record->voucherCode && $record->voucherCode->voucher) {
                            return $record->voucherCode->voucher->final_price ?? 0;
                        }
                        return $record->categoryTicketType?->price ?? 0;
                    } catch (\Exception $e) {
                        return 0;
                    }
                }),
            ExportColumn::make('voucher_code_value')
                ->label('Kode Voucher')
                ->getStateUsing(function(Registration $record){
                    try {
                        return $record->voucherCode?->code ?? "";
                    } catch (\Exception $e) {
                        return "";
                    }
                }),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your registration export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }

    public function getFormats(): array
    {
        return [
            ExportFormat::Csv,
            ExportFormat::Xlsx,
        ];
    }

    public function getFileName(Export $export): string
    {
        return "registration-{$export->getKey()}";
    }
}
