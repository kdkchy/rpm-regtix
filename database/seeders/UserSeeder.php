<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Create roles
        $superAdmin = Role::firstOrCreate(['name' => 'superadmin', 'label' => 'Super Admin']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'label' => 'Administrator']);
        $operatorRole = Role::firstOrCreate(['name' => 'operator', 'label' => 'Operator']);


        // Create the admin user
        User::firstOrCreate([
            'email' => 'administrator@regtix.id',
        ], [
            'name' => 'Administrator',
            'password' => Hash::make('regtix@123'),
            'role_id' => $superAdmin->id,  // Set the role_id to the admin role
        ]);

        $admin = User::firstOrCreate([
            'email' => 'admin@regtix.id',
        ], [
            'name' => 'Admin Event',
            'password' => Hash::make('regtix@123'),
            'role_id' => $adminRole->id,  // Set the role_id to the admin role          
        ]);
        $admin->events()->syncWithoutDetaching([1]);


        // Create the operator user
        $operators = [
            [
                'email' => 'operator1@regtix.id',
                'name' => 'Operator 1',
                'password' => Hash::make('regtix@123op'),
                'role_id' => $operatorRole->id,  // Set the role_id to the operator role
                'event_id' => 1
            ],
            [
                'email' => 'operator2@regtix.id',
                'name' => 'Operator 2',
                'password' => Hash::make('regtix@123op'),
                'role_id' => $operatorRole->id,  // Set the role_id to the operator role
                'event_id' => 1
            ],
            [
                'email' => 'operator3@regtix.id',
                'name' => 'Operator 3',
                'password' => Hash::make('regtix@123op'),
                'role_id' => $operatorRole->id,  // Set the role_id to the operator role
                'event_id' => 1
            ],
            [
                'email' => 'operator4@regtix.id',
                'name' => 'Operator 4',
                'password' => Hash::make('regtix@123op'),
                'role_id' => $operatorRole->id,  // Set the role_id to the operator role
                'event_id' => 2
            ],
            [
                'email' => 'operator5@regtix.id',
                'name' => 'Operator 5',
                'password' => Hash::make('regtix@123op'),
                'role_id' => $operatorRole->id,  // Set the role_id to the operator role
                'event_id' => 2
            ]
        ];
        foreach ($operators as $operator) {
            // Simpan dulu event_id, lalu hapus agar tidak error di create
            $eventId = $operator['event_id'];
            unset($operator['event_id']);

            // Buat user
            $user = User::create($operator);

            // Attach event via relasi many-to-many
            $user->events()->attach($eventId);
        }
    }
}
