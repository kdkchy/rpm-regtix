<?php

namespace App\Filament\Resources\RegistrationResource\Pages;

use App\Filament\Resources\RegistrationResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditRegistration extends EditRecord
{
    protected static string $resource = RegistrationResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);
        
        $user = Auth::user();
        $event = $this->record->categoryTicketType?->category?->event;
        
        // Jika bukan superadmin dan registration locked, tampilkan notification
        if ($user->role->name !== 'superadmin' && $event && !($event->allow_registration_edit ?? true)) {
            Notification::make()
                ->title('Edit Locked')
                ->body('This registration is locked for editing. Only superadmin can modify it.')
                ->warning()
                ->persistent()
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        $user = Auth::user();
        $event = $this->record->categoryTicketType?->category?->event;
        $isLocked = $user->role->name !== 'superadmin' && $event && !($event->allow_registration_edit ?? true);
        
        $actions = parent::getFormActions();
        
        // Disable save button jika locked
        if ($isLocked) {
            foreach ($actions as $action) {
                if (method_exists($action, 'disabled')) {
                    $action->disabled(true);
                }
            }
        }
        
        return $actions;
    }

    protected function getHeaderActions(): array
    {
        $user = Auth::user();
        $isSuperAdmin = $user->role->name === 'superadmin';
        
        return [
            Actions\DeleteAction::make()
                ->visible($isSuperAdmin),
        ];
    }
}
