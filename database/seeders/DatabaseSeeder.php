<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $categories = [
            ['name' => 'Hardware', 'description' => 'PC, peripherals, printers'],
            ['name' => 'Software', 'description' => 'OS, applications, licenses'],
            ['name' => 'Network', 'description' => 'Wi-Fi, LAN, VPN, internet'],
            ['name' => 'Account/Access', 'description' => 'Logins, permissions, passwords'],
            ['name' => 'Others', 'description' => 'Anything not covered above'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['name' => $category['name']], $category);
        }

        User::updateOrCreate(
            ['user_id' => 'admin'],
            [
                'name' => 'System Administrator',
                'email' => 'admin@lixicon.local',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
                'department' => 'IT',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['user_id' => 'support1'],
            [
                'name' => 'Budi IT Support',
                'email' => 'budi@lixicon.local',
                'password' => Hash::make('password'),
                'role' => User::ROLE_IT_SUPPORT,
                'department' => 'IT',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['user_id' => 'support2'],
            [
                'name' => 'Sari IT Support',
                'email' => 'sari@lixicon.local',
                'password' => Hash::make('password'),
                'role' => User::ROLE_IT_SUPPORT,
                'department' => 'IT',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['user_id' => 'staff1'],
            [
                'name' => 'Andi Staff',
                'email' => 'andi@lixicon.local',
                'password' => Hash::make('password'),
                'role' => User::ROLE_STAFF,
                'department' => 'Finance',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['user_id' => 'staff2'],
            [
                'name' => 'Citra Staff',
                'email' => 'citra@lixicon.local',
                'password' => Hash::make('password'),
                'role' => User::ROLE_STAFF,
                'department' => 'Marketing',
                'is_active' => true,
            ]
        );

        $items = [
            ['serial_number' => 'SN-LAP-0001', 'item_name' => 'ThinkPad X1 Carbon', 'brand_name' => 'Lenovo', 'mac_address' => '00:1A:2B:3C:4D:5E', 'type' => 'Laptop', 'condition' => Item::CONDITION_GOOD],
            ['serial_number' => 'SN-MON-0002', 'item_name' => 'UltraSharp U2723QE', 'brand_name' => 'Dell', 'mac_address' => null, 'type' => 'Monitor', 'condition' => Item::CONDITION_NEW],
            ['serial_number' => 'SN-RTR-0003', 'item_name' => 'Archer AX73', 'brand_name' => 'TP-Link', 'mac_address' => 'A4:B1:C2:D3:E4:F5', 'type' => 'Router', 'condition' => Item::CONDITION_GOOD],
            ['serial_number' => 'SN-PRJ-0004', 'item_name' => 'EB-X06 Projector', 'brand_name' => 'Epson', 'mac_address' => null, 'type' => 'Proyektor', 'condition' => Item::CONDITION_MINOR_DAMAGE],
        ];

        foreach ($items as $item) {
            Item::firstOrCreate(
                ['serial_number' => $item['serial_number']],
                [...$item, 'status' => Item::STATUS_AVAILABLE],
            );
        }
    }
}
