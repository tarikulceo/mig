<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Customer;
use App\Models\SalesRepresentative;
use App\Models\SalesTerritory;
use App\Models\RetailStore;
use App\Models\SalesCommission;
use Illuminate\Support\Facades\Log;

class OrderAssignmentService
{
    /**
     * Automatically assign order to sales representative based on customer location
     */
    public function assignOrderToSalesRep(Order $order)
    {
        try {
            // Skip if order already has sales rep assigned
            if ($order->sales_rep_id) {
                return $order;
            }

            $customer = $order->user;
            if (!$customer) {
                Log::info("Order {$order->id} has no customer assigned");
                return $order;
            }

            // Get customer's location information
            $customerLocation = $this->getCustomerLocation($customer, $order);
            
            if (!$customerLocation) {
                Log::info("Could not determine location for customer {$customer->id}");
                return $order;
            }

            // Find appropriate sales representative
            $salesRep = $this->findSalesRepForLocation($customerLocation);
            
            if ($salesRep) {
                $order->sales_rep_id = $salesRep->id;
                $order->order_source = $order->order_source ?? 'online';
                $order->save();

                // Create customer-sales rep relationship if not exists
                $this->assignCustomerToSalesRep($customer, $salesRep);

                // Calculate commission for the order
                $this->calculateOrderCommission($order, $salesRep);

                Log::info("Order {$order->id} assigned to sales rep {$salesRep->id}");
            }

            return $order;

        } catch (\Exception $e) {
            Log::error("Error assigning order {$order->id} to sales rep: " . $e->getMessage());
            return $order;
        }
    }

    /**
     * Get customer location information
     */
    protected function getCustomerLocation($customer, $order)
    {
        $location = null;

        // Try to get location from order shipping address
        if ($order->shipping_address) {
            $shippingAddress = is_string($order->shipping_address) 
                ? json_decode($order->shipping_address, true) 
                : $order->shipping_address;
            
            if (is_array($shippingAddress)) {
                $location = [
                    'country' => $shippingAddress['country'] ?? null,
                    'state' => $shippingAddress['state'] ?? null,
                    'city' => $shippingAddress['city'] ?? null,
                    'postal_code' => $shippingAddress['postal_code'] ?? null,
                    'address' => $shippingAddress['address'] ?? null
                ];
            }
        }

        // Try to get location from customer profile
        if (!$location && $customer) {
            $location = [
                'country' => $customer->country ?? null,
                'state' => $customer->state ?? null,
                'city' => $customer->city ?? null,
                'postal_code' => $customer->postal_code ?? null,
                'address' => $customer->address ?? null
            ];
        }

        return $location;
    }

    /**
     * Find sales representative for given location
     */
    protected function findSalesRepForLocation($location)
    {
        if (!$location || !$location['country']) {
            return null;
        }

        // Find territory that matches the location
        $territoryQuery = SalesTerritory::where('is_active', 1);

        // Match by country
        if ($location['country']) {
            $territoryQuery->where('country_id', $location['country']);
        }

        // Match by state if available
        if ($location['state']) {
            $territoryQuery->where(function($query) use ($location) {
                $query->where('state_id', $location['state'])
                      ->orWhereNull('state_id');
            });
        }

        // Match by city if available
        if ($location['city']) {
            $territoryQuery->where(function($query) use ($location) {
                $query->whereJsonContains('cities', $location['city'])
                      ->orWhereNull('cities');
            });
        }

        // Match by postal code if available
        if ($location['postal_code']) {
            $territoryQuery->where(function($query) use ($location) {
                $query->whereJsonContains('zip_codes', $location['postal_code'])
                      ->orWhereNull('zip_codes');
            });
        }

        $territory = $territoryQuery->first();

        if (!$territory) {
            // If no specific territory found, try to find a general territory for the country
            $territory = SalesTerritory::where('is_active', 1)
                ->where('country_id', $location['country'])
                ->whereNull('state_id')
                ->first();
        }

        if ($territory) {
            // Find active sales rep in this territory
            $salesRep = SalesRepresentative::where('territory_id', $territory->id)
                ->where('status', 1)
                ->first();
            
            return $salesRep;
        }

        // If no territory-based assignment, find any available sales rep
        return SalesRepresentative::where('status', 1)->first();
    }

    /**
     * Assign customer to sales representative
     */
    protected function assignCustomerToSalesRep($customer, $salesRep)
    {
        // Create customer record if doesn't exist
        $customerRecord = Customer::firstOrCreate(
            ['user_id' => $customer->id],
            [
                'sales_rep_id' => $salesRep->id,
                'territory_id' => $salesRep->territory_id
            ]
        );

        // Update sales rep assignment if not set
        if (!$customerRecord->sales_rep_id) {
            $customerRecord->update([
                'sales_rep_id' => $salesRep->id,
                'territory_id' => $salesRep->territory_id
            ]);
        }
    }

    /**
     * Calculate commission for order
     */
    protected function calculateOrderCommission($order, $salesRep)
    {
        // Skip if commission already calculated
        if ($order->commission_calculated) {
            return;
        }

        // Check if commission already exists
        $existingCommission = SalesCommission::where('order_id', $order->id)
            ->where('sales_rep_id', $salesRep->id)
            ->first();

        if ($existingCommission) {
            return;
        }

        // Calculate commission amount
        $commissionRate = $salesRep->commission_rate ?? 5; // Default 5%
        $saleAmount = $order->grand_total;
        $commissionAmount = ($saleAmount * $commissionRate) / 100;

        // Create commission record
        SalesCommission::create([
            'sales_rep_id' => $salesRep->id,
            'order_id' => $order->id,
            'commission_amount' => $commissionAmount,
            'commission_rate' => $commissionRate,
            'sale_amount' => $saleAmount,
            'commission_type' => 'order',
            'payment_status' => 'pending',
            'status' => 'pending'
        ]);

        // Mark order as commission calculated
        $order->update(['commission_calculated' => 1]);
    }

    /**
     * Assign order to nearest retail store
     */
    public function assignOrderToNearestStore(Order $order)
    {
        try {
            $customerLocation = $this->getCustomerLocation($order->user, $order);
            
            if (!$customerLocation || !$order->sales_rep_id) {
                return $order;
            }

            // Find nearest retail store managed by the assigned sales rep
            $nearestStore = $this->findNearestStore($customerLocation, $order->sales_rep_id);
            
            if ($nearestStore) {
                $order->retail_store_id = $nearestStore->id;
                $order->is_store_order = true;
                $order->save();
                
                Log::info("Order {$order->id} assigned to nearest store {$nearestStore->id}");
            }

            return $order;

        } catch (\Exception $e) {
            Log::error("Error assigning order {$order->id} to nearest store: " . $e->getMessage());
            return $order;
        }
    }

    /**
     * Find nearest retail store
     */
    protected function findNearestStore($location, $salesRepId)
    {
        $query = RetailStore::where('sales_rep_id', $salesRepId)
            ->where('status', 'active');

        // If we have GPS coordinates, find by distance
        if (isset($location['latitude']) && isset($location['longitude'])) {
            $lat = $location['latitude'];
            $lng = $location['longitude'];
            
            // Calculate distance using Haversine formula
            $query->selectRaw("*, 
                ( 6371 * acos( cos( radians(?) ) * 
                cos( radians( latitude ) ) * 
                cos( radians( longitude ) - radians(?) ) + 
                sin( radians(?) ) * 
                sin( radians( latitude ) ) ) ) AS distance", [$lat, $lng, $lat])
                ->orderBy('distance');
        } else {
            // Match by city/state
            if ($location['city']) {
                $query->where('city', $location['city']);
            }
            if ($location['state']) {
                $query->where('state', $location['state']);
            }
        }

        return $query->first();
    }

    /**
     * Bulk assign orders to sales representatives
     */
    public function bulkAssignOrders($orderIds = null)
    {
        $query = Order::whereNull('sales_rep_id');
        
        if ($orderIds) {
            $query->whereIn('id', $orderIds);
        }

        $orders = $query->get();
        $assignedCount = 0;

        foreach ($orders as $order) {
            $assignedOrder = $this->assignOrderToSalesRep($order);
            if ($assignedOrder->sales_rep_id) {
                $assignedCount++;
            }
        }

        return [
            'total_orders' => $orders->count(),
            'assigned_orders' => $assignedCount
        ];
    }
}