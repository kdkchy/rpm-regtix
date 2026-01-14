<?php

namespace App\Filament\Resources\RegistrationResource\Pages;

use App\Filament\Resources\RegistrationResource;
use Filament\Pages\Page;
use App\Models\Registration;
use Filament\Actions\EditAction;

class ValidateRegistration extends Page
{
    protected static string $view = 'filament.resources.registration-resource.pages.validate-registration'; // Ganti dengan view yang sesuai

    public $registration;

    public function mount(Registration $registration)
    {
        $this->registration = $registration;
    }

    public function handleValidation()
    {
        return EditAction::make()
            ->mutateFormDataUsing(function (array $data): array {
                $data['is_validated'] = true;

                return $data;
            });
    }
}
