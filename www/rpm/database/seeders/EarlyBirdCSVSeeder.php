<?php

namespace Database\Seeders;

use App\Models\Registration;
use App\Models\TicketType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use League\Csv\Reader;

class EarlyBirdCSVSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $file = database_path('seeders/data/early-bird.csv');

        if (!file_exists($file) || !is_readable($file)) {
            throw new \Exception("CSV file not found or not readable.");
        }

        // Ambil ticket_type_id dari nama
        $ticketType = TicketType::where('name', 'Early Bird')->first();

        if (!$ticketType) {
            throw new \Exception("Ticket type 'Early Bird' not found.");
        }

        $ticketTypeId = $ticketType->id;

        $header = null;

        if (($handle = fopen($file, 'r')) !== false) {
            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                if (!$header) {
                    $header = $row;
                } else {

                    $data = array_combine($header, $row);
                    // dd($data);
                    if (isset($data['phone'])) {
                        $data['phone'] = str_replace(["-", " ", "'"], '', $data['phone']);
                    }
                    if (!preg_match('/^(?:\+62|0)/', $data['phone'])) {
                        $data['phone'] = '0' . $data['phone'];
                    }

                    if (isset($data['emergency_contact_phone'])) {
                        $data['emergency_contact_phone'] = str_replace(["-", " ", "'"], '', $data['emergency_contact_phone']);
                    }
                    if (!preg_match('/^(?:\+62|0)/', $data['emergency_contact_phone'])) {
                        $data['emergency_contact_phone'] = '0' . $data['emergency_contact_phone'];
                    }

                    $data['ticket_type_id'] = $ticketTypeId;

                    Registration::create($data);
                }
            }
            fclose($handle);
        }
    }
}
