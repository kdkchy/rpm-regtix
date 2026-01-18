<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    protected function afterCreate(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user) {
            $user->events()->syncWithoutDetaching([$this->record->id]);
        }
    }

    public function mount(int | string $record): void
    {
        parent::mount($record);
        
        $user = Auth::user();
        // Jika bukan superadmin dan event locked, tampilkan notification
        if ($user->role->name !== 'superadmin' && !($this->record->allow_event_edit ?? true)) {
            Notification::make()
                ->title('Edit Locked')
                ->body('This event is locked for editing. Only superadmin can modify it.')
                ->warning()
                ->persistent()
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        $user = Auth::user();
        $isLocked = $user->role->name !== 'superadmin' && !($this->record->allow_event_edit ?? true);
        
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
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
