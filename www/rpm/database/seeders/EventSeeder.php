<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $event = Event::create([
            'name' => 'Sanga Sanga Run 2025',
            'start_date' => '2025-05-12 06:00:00',
            'end_date' => '2025-05-12 12:00:00',
            'location' => 'Mango Lango Lake, Jl. Sawo Bakbakan, Bitera, Gianyar, Bali',
            'code_prefix' => 'SS99',
        ]);

        $ticketTypes = [
            ['name' => 'Flash Sale', 'price' => 99000, 'quota' => 100],
            ['name' => 'Early Bird', 'price' => 159000, 'quota' => 350],
            ['name' => 'Special Price', 'price' => 199000, 'quota' => 150],
            ['name' => 'Regular', 'price' => 230000, 'quota' => 150],
            ['name' => 'Invitation', 'price' => 0, 'quota' => 200],
            ['name' => 'Community Price', 'price' => 209000, 'quota' => 50],
            ['name' => 'Sangasian', 'price' => 99000, 'quota' => 60],
        ];
        foreach ($ticketTypes as $type) {
            TicketType::create([
                'name' => $type['name'],
            ]);
        }
    }
}
