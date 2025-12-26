<?php

namespace App\Filament\Resources\RegistrationResource\Pages;

use App\Filament\Resources\RegistrationResource;
use App\Helpers\EmailSender;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class ViewRegistration extends EditRecord
{
    protected static string $resource = RegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('send_email')
                ->label('Send Email')
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
                        ->maxLength(255),
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
            Action::make('save')
                ->label('Save')
                ->icon('heroicon-m-inbox-arrow-down')
                ->requiresConfirmation()
                ->color('success')
                ->action(function ($record, $livewire) {
                    // Validasi form terlebih dahulu
                    $data = $livewire->form->getState();
                    $livewire->form->validate();

                    // Update record dengan data tervalidasi + tambahan kolom
                    $record->update([
                        ...$data
                    ]);

                    // Kirim notifikasi "saved"
                    $livewire->getSavedNotification()?->send();
                }),
            Action::make('validate')
                ->label('Validate')
                ->icon('heroicon-m-check-badge')
                ->requiresConfirmation()
                ->color('success')
                ->action(function ($record, $livewire) {
                    // Validasi form terlebih dahulu
                    $data = $livewire->form->getState();
                    $livewire->form->validate();

                    // Update record dengan data tervalidasi + tambahan kolom
                    $record->update([
                        'is_validated' => true,
                        'validated_by' => Auth::id(),
                    ]);

                    // Kirim notifikasi "saved"
                    $livewire->getSavedNotification()?->send();
                }),


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


            $this->getCancelFormAction(),
        ];
    }
}
