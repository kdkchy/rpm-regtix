<?php

namespace Database\Seeders;

use App\Models\CategoryTicketType;
use App\Models\Registration;
use App\Models\TicketType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RegistrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $ticketTypeId = CategoryTicketType::first()->id;
        Registration::create([
            'category_ticket_type_id' => $ticketTypeId,
            'registration_date' => now(),
            'full_name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'phone' => '081234567890',
            'gender' => 'Male',
            'place_of_birth' => 'Jakarta',
            'dob' => '1990-01-01',
            'address' => 'Jl. Mawar No. 123',
            'district' => 'Gambir',
            'province' => 'DKI Jakarta',
            'country' => 'Indonesia',
            'id_card_type' => 'KTP',
            'id_card_number' => '1234567890',
            'emergency_contact_name' => 'Jane Doe',
            'emergency_contact_phone' => '081234567891',
            'blood_type' => 'O',
            'nationality' => 'Indonesian',
            'jersey_size' => 'L',
            'community_name' => 'Runners Club',
            'bib_name' => 'JOHN',
            'reg_id' => 'REG123456',
            'is_validated' => false,
        ]);
    }
}
