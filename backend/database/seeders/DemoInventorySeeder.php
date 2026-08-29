<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoInventorySeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->firstOrCreate(
            ['email' => 'owner@stocklane.test'],
            [
                'name' => 'StockLane Owner',
                'password' => Hash::make('password'),
            ],
        );

        $skus = [
            ['sku' => 'SME-BOLT-M6', 'name' => 'Hex Bolt M6 Pack', 'quantity' => 48, 'reorder_at' => 12, 'unit_cost_cents' => 150],
            ['sku' => 'SME-NUT-M6', 'name' => 'Hex Nut M6 Pack', 'quantity' => 8, 'reorder_at' => 10, 'unit_cost_cents' => 80],
            ['sku' => 'SME-FILTER-01', 'name' => 'Oil Filter OEM', 'quantity' => 4, 'reorder_at' => 5, 'unit_cost_cents' => 45000],
            ['sku' => 'SME-GREASE-01', 'name' => 'Lithium Grease Tube', 'quantity' => 22, 'reorder_at' => 6, 'unit_cost_cents' => 12000],
        ];

        foreach ($skus as $row) {
            Product::query()->updateOrCreate(
                ['sku' => $row['sku']],
                array_merge($row, ['user_id' => $owner->id]),
            );
        }
    }
}
