<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $studentRole = Role::where('name', 'student')->first();

        User::create([
            'name'     => 'René Estudiante',
            'email'    => 'reneprh2013@gmail.com',
            'password' => bcrypt('pangaleluney2013'),
            'role_id'  => $studentRole->id,
        ]);
    }
}