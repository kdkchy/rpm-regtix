<?php

use App\Filament\Resources\RegistrationResource\Pages\ValidateRegistration;
use App\Http\Controllers\Webhook\MidtransWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});
Route::get('/admin/registrations/{registration}/validate', ValidateRegistration::class)->name('registrations.validate');
Route::get('/admin/registrations/{registration}/print', function ($id) {
    $registration = \App\Models\Registration::findOrFail($id);
    return view('print.registration', compact('registration'));
})->name('registration.print');
