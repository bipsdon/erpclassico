<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed one user per role.
     * All passwords default to "password" — change before production.
     */
    public function run(): void
    {
        $users = [
            [
                'name'     => 'Pipeline Manager',
                'email'    => 'pipeline@erpclassico.test',
                'role'     => 'pipeline_manager',
                'password' => Hash::make('password'),
            ],
            [
                'name'     => 'Designer',
                'email'    => 'designer@erpclassico.test',
                'role'     => 'designer',
                'password' => Hash::make('password'),
            ],
            [
                'name'     => 'Printing Manager',
                'email'    => 'printing@erpclassico.test',
                'role'     => 'printing_manager',
                'password' => Hash::make('password'),
            ],
            [
                'name'     => 'Sewing Manager',
                'email'    => 'sewing@erpclassico.test',
                'role'     => 'sewing_manager',
                'password' => Hash::make('password'),
            ],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                array_merge($data, ['is_active' => true])
            );
        }
    }
}
