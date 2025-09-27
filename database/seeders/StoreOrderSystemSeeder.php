<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SalesRepresentative;
use App\Models\RetailStore;
use App\Models\StoreVisit;
use App\Models\StoreOrder;
use App\Models\StoreOrderItem;
use App\Models\User;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class StoreOrderSystemSeeder extends Seeder
{
    public function run()
    {
        // Create a test sales representative user
        $salesRepUser = User::firstOrCreate([
            'email' => 'salesrep@test.com'
        ], [
            'name' => 'John Sales Rep',
            'password' => Hash::make('password'),
            'user_type' => 'sales_rep',
            'email_verified_at' => now()
        ]);

        // Create sales representative record
        $salesRep = SalesRepresentative::firstOrCreate([
            'user_id' => $salesRepUser->id
        ], [
            'employee_id' => 'SR001',
            'hire_date' => Carbon::now()->subYear(),
            'base_salary' => 50000,
            'commission_rate' => 5.0,
            'status' => 'active',
            'created_by' => 1
        ]);

        // Create test retail stores
        $stores = [];
        for ($i = 1; $i <= 3; $i++) {
            $store = RetailStore::firstOrCreate([
                'store_code' => 'STORE' . str_pad($i, 3, '0', STR_PAD_LEFT)
            ], [
                'name' => "Test Store #$i",
                'description' => "This is test retail store number $i",
                'owner_name' => "Owner $i",
                'phone' => '555-000-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'email' => "store$i@test.com",
                'address' => "$i Main Street",
                'city' => 'Test City',
                'state' => 'Test State',
                'country' => 'Test Country',
                'postal_code' => str_pad($i * 1000, 5, '0', STR_PAD_LEFT),
                'latitude' => 40.7128 + ($i * 0.01),
                'longitude' => -74.0060 + ($i * 0.01),
                'store_type' => 'retail',
                'staff_count' => rand(2, 10),
                'sales_rep_id' => $salesRep->id,
                'monthly_target' => rand(10000, 50000),
                'yearly_target' => rand(120000, 600000),
                'status' => 'active',
                'established_date' => Carbon::now()->subYears(rand(1, 5)),
                'created_by' => $salesRepUser->id
            ]);
            $stores[] = $store;
        }

        // Create some store visits
        foreach ($stores as $store) {
            for ($i = 0; $i < 2; $i++) {
                StoreVisit::firstOrCreate([
                    'retail_store_id' => $store->id,
                    'sales_rep_id' => $salesRep->id,
                    'visit_date' => Carbon::now()->addDays($i - 1)
                ], [
                    'check_in_time' => '09:00:00',
                    'check_out_time' => '10:00:00',
                    'purpose' => $i == 0 ? 'Sales Visit' : 'Follow-up',
                    'notes' => "Test visit notes for store {$store->name}",
                    'order_amount' => rand(500, 5000),
                    'visit_status' => 'completed',
                    'order_placed' => true,
                    'visit_type' => 'regular'
                ]);
            }
        }

        // Get some products to create orders with
        $products = Product::limit(5)->get();
        
        if ($products->count() > 0) {
            // Create some test orders
            foreach ($stores as $store) {
                $visit = StoreVisit::where('retail_store_id', $store->id)
                                 ->where('sales_rep_id', $salesRep->id)
                                 ->first();
                
                $order = StoreOrder::firstOrCreate([
                    'order_code' => 'STO' . date('Ymd') . str_pad($store->id, 4, '0', STR_PAD_LEFT)
                ], [
                    'retail_store_id' => $store->id,
                    'sales_rep_id' => $salesRep->id,
                    'store_visit_id' => $visit ? $visit->id : null,
                    'order_date' => Carbon::now(),
                    'expected_delivery_date' => Carbon::now()->addDays(7),
                    'subtotal' => 0,
                    'tax_amount' => 0,
                    'shipping_amount' => 50,
                    'discount_amount' => 0,
                    'grand_total' => 0,
                    'order_status' => 'pending',
                    'payment_status' => 'pending',
                    'payment_method' => 'cash',
                    'notes' => 'Test order for ' . $store->name,
                    'created_by' => $salesRepUser->id
                ]);

                // Add order items
                $subtotal = 0;
                foreach ($products->take(rand(1, 3)) as $product) {
                    $quantity = rand(1, 5);
                    $unitPrice = $product->unit_price ?? rand(10, 100);
                    $total = $quantity * $unitPrice;
                    $subtotal += $total;

                    StoreOrderItem::firstOrCreate([
                        'store_order_id' => $order->id,
                        'product_id' => $product->id
                    ], [
                        'product_name' => $product->name,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'discount_amount' => 0,
                        'total_amount' => $total
                    ]);
                }

                // Update order totals
                $tax = $subtotal * 0.1; // 10% tax
                $grandTotal = $subtotal + $tax + $order->shipping_amount - $order->discount_amount;
                
                $order->update([
                    'subtotal' => $subtotal,
                    'tax_amount' => $tax,
                    'grand_total' => $grandTotal
                ]);
            }
        }

        $this->command->info('Store Order System seeded successfully!');
        $this->command->info("Created:");
        $this->command->info("- Sales Rep: {$salesRep->employee_id} ({$salesRepUser->email})");
        $this->command->info("- Stores: " . count($stores));
        $this->command->info("- Store Visits: " . StoreVisit::count());
        $this->command->info("- Store Orders: " . StoreOrder::count());
        $this->command->info("- Order Items: " . StoreOrderItem::count());
    }
}