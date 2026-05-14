<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        $adminRole = Role::where('name', 'admin')->first();
        User::create([
            'name' => 'Admin',
            'email' => 'dxhunterpro@gmail.com',
            'password' => bcrypt('pangaleluney2013'),
            'role_id' => $adminRole->id,
        ]);
    }
}
