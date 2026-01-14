<!-- resources/views/filament/resources/registration-resource/pages/validate-registration.blade.php -->

<x-filament::page>
    <form wire:submit.prevent="handleValidation">
        <div class="space-y-4">
            <h2 class="text-lg font-semibold">Are you sure you want to validate this registration?</h2>
            <p>Registration ID: {{ $registration->reg_id }}</p>
            <p>Name: {{ $registration->full_name }}</p>

            <!-- You can add more fields from the registration model as needed -->
        </div>

        <div class="mt-4">
            <x-filament::button type="submit" color="success">Yes, Validate</x-filament::button>
            <x-filament::button type="button" color="gray" wire:click="cancel">Cancel</x-filament::button>
        </div>
    </form>
</x-filament::page>
