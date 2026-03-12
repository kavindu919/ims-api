<?php

namespace Database\Seeders;

use App\Models\Cupboard;
use App\Models\InventoryItem;
use App\Models\StoragePlace;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name'       => 'System Admin',
            'email'      => 'admin@ceyntics.com',
            'password'   => Hash::make('password123'),
            'role'       => 'admin',
            'is_active'  => true,
            'created_by' => null,
        ]);

        User::create([
            'name'       => 'Staff User',
            'email'      => 'staff@ceyntics.com',
            'password'   => Hash::make('password123'),
            'role'       => 'staff',
            'is_active'  => true,
            'created_by' => $admin->id,
        ]);

        $cupboard = Cupboard::create([
            'name'     => 'Cabinet A',
            'location' => 'Server Room',
        ]);

        $place = StoragePlace::create([
            'cupboard_id' => $cupboard->id,
            'name'        => 'Shelf 1',
            'description' => 'Top shelf',
        ]);

        InventoryItem::create([
            'name'             => 'Network Switch',
            'code'             => 'NET-SW-001',
            'quantity'         => 3,
            'description'      => 'Cisco 24-port switch',
            'storage_place_id' => $place->id,
            'status'           => 'in_store',
        ]);
    }
}
