<?php

namespace App\Filament\Exports;

use App\Models\Registration;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Facades\Log;

class RegistrationExporter extends Exporter
{
    protected static ?string $model = Registration::class;
    
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('registration_code')->label('Kode Registrasi'),
            ExportColumn::make('ticketType.name')->label('Tiket'),
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
            ExportColumn::make('voucherCode.code')->label('Kode Voucher'),
            ExportColumn::make('gross_amount')->label('Gross Amount')
            ->formatStateUsing(function(Registration $record){
                return $record->voucherCode?->voucher?->final_price ?? $record->categoryTicketType?->price ?? 0;
            }),
            ExportColumn::make('invitation_code')->label('Kode Undangan'),
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
