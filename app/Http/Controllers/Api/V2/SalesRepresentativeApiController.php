<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SalesRepresentative;
use App\Models\StoreVisit;
use App\Models\StoreOrder;
use App\Models\RetailStore;
use App\Models\SalesActivity;
use App\Models\SalesCommission;
use App\Models\SalesTarget;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class SalesRepresentativeApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get sales representative dashboard data
     */
    public function dashboard(Request $request)
    {
        try {
            $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
            
            if (!$salesRep) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sales representative not found',
                    'data' => null
                ], 404);
            }

            $today = Carbon::today();
            
            // Today's statistics
            $todayStats = [
                'visits' => $salesRep->storeVisits()->whereDate('visit_date', $today)->count(),
                'completed_visits' => $salesRep->storeVisits()
                    ->whereDate('visit_date', $today)
                    ->where('visit_status', 'completed')
                    ->count(),
                'orders' => $salesRep->storeOrders()->whereDate('created_at', $today)->count(),
                'sales_amount' => (float) $salesRep->storeOrders()
                    ->whereDate('created_at', $today)
                    ->sum('grand_total'),
                'pending_orders' => $salesRep->storeOrders()
                    ->where('order_status', 'pending')
                    ->count()
            ];

            // Today's schedule
            $todaySchedule = $salesRep->storeVisits()
                ->with(['retailStore:id,name,address,latitude,longitude'])
                ->whereDate('visit_date', $today)
                ->orderBy('scheduled_time')
                ->get()
                ->map(function ($visit) {
                    return [
                        'id' => $visit->id,
                        'store_name' => $visit->retailStore->name ?? 'Unknown Store',
                        'store_address' => $visit->retailStore->address ?? '',
                        'store_location' => [
                            'latitude' => $visit->retailStore->latitude,
                            'longitude' => $visit->retailStore->longitude
                        ],
                        'scheduled_time' => $visit->scheduled_time ? $visit->scheduled_time->format('H:i') : null,
                        'visit_status' => $visit->visit_status,
                        'purpose' => $visit->purpose,
                        'can_start' => $visit->visit_status === 'pending',
                        'can_complete' => $visit->visit_status === 'in_progress'
                    ];
                });

            // Performance metrics
            $monthlyMetrics = $salesRep->getPerformanceMetrics('monthly');
            
            // Target achievement
            $targetAchievement = $salesRep->getTargetAchievement('sales', 'monthly');

            return response()->json([
                'success' => true,
                'message' => 'Dashboard data retrieved successfully',
                'data' => [
                    'sales_rep' => [
                        'id' => $salesRep->id,
                        'name' => $salesRep->full_name,
                        'employee_id' => $salesRep->employee_id,
                        'designation' => $salesRep->designation,
                        'territory' => $salesRep->territory->name ?? null,
                        'current_location' => [
                            'latitude' => $salesRep->current_latitude,
                            'longitude' => $salesRep->current_longitude,
                            'last_updated' => $salesRep->last_location_update
                        ]
                    ],
                    'today_stats' => $todayStats,
                    'today_schedule' => $todaySchedule,
                    'monthly_metrics' => $monthlyMetrics,
                    'target_achievement' => $targetAchievement
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving dashboard data: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Update GPS location
     */
    public function updateLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric',
            'device_info' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
            
            if (!$salesRep) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sales representative not found'
                ], 404);
            }

            $salesRep->updateLocationFromRequest(
                $request->latitude,
                $request->longitude,
                $request->device_info
            );

            return response()->json([
                'success' => true,
                'message' => 'Location updated successfully',
                'data' => [
                    'latitude' => $salesRep->current_latitude,
                    'longitude' => $salesRep->current_longitude,
                    'updated_at' => $salesRep->last_location_update
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating location: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get nearby stores
     */
    public function getNearbyStores(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
            
            if (!$salesRep) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sales representative not found'
                ], 404);
            }

            $radius = $request->get('radius', 10);
            $lat = $request->latitude;
            $lng = $request->longitude;

            $nearbyStores = RetailStore::select([
                'id', 'name', 'address', 'phone', 'latitude', 'longitude', 'store_type',
                \DB::raw("(6371 * acos(cos(radians($lat)) 
                    * cos(radians(latitude)) 
                    * cos(radians(longitude) - radians($lng)) 
                    + sin(radians($lat)) 
                    * sin(radians(latitude)))) AS distance")
            ])
            ->where('sales_rep_id', $salesRep->id)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->having('distance', '<', $radius)
            ->orderBy('distance')
            ->limit(20)
            ->get()
            ->map(function ($store) {
                return [
                    'id' => $store->id,
                    'name' => $store->name,
                    'address' => $store->address,
                    'phone' => $store->phone,
                    'store_type' => $store->store_type,
                    'location' => [
                        'latitude' => (float) $store->latitude,
                        'longitude' => (float) $store->longitude
                    ],
                    'distance_km' => round($store->distance, 2)
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Nearby stores retrieved successfully',
                'data' => [
                    'stores' => $nearbyStores,
                    'total_count' => $nearbyStores->count(),
                    'search_radius' => $radius
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving nearby stores: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get today's visits
     */
    public function getTodayVisits(Request $request)
    {
        try {
            $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
            
            if (!$salesRep) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sales representative not found'
                ], 404);
            }

            $visits = $salesRep->storeVisits()
                ->with(['retailStore:id,name,address,phone,latitude,longitude'])
                ->whereDate('visit_date', Carbon::today())
                ->orderBy('scheduled_time')
                ->get()
                ->map(function ($visit) {
                    return [
                        'id' => $visit->id,
                        'store' => [
                            'id' => $visit->retailStore->id ?? null,
                            'name' => $visit->retailStore->name ?? 'Unknown Store',
                            'address' => $visit->retailStore->address ?? '',
                            'phone' => $visit->retailStore->phone ?? '',
                            'location' => [
                                'latitude' => $visit->retailStore->latitude,
                                'longitude' => $visit->retailStore->longitude
                            ]
                        ],
                        'scheduled_time' => $visit->scheduled_time ? $visit->scheduled_time->format('H:i') : null,
                        'visit_status' => $visit->visit_status,
                        'purpose' => $visit->purpose,
                        'notes' => $visit->notes,
                        'check_in_time' => $visit->check_in_time ? $visit->check_in_time->format('H:i') : null,
                        'check_out_time' => $visit->check_out_time ? $visit->check_out_time->format('H:i') : null,
                        'order_amount' => (float) $visit->order_amount,
                        'photos' => $visit->photos ? array_map(function($photo) {
                            return Storage::url($photo);
                        }, $visit->photos) : [],
                        'actions' => [
                            'can_start' => $visit->visit_status === 'pending',
                            'can_complete' => $visit->visit_status === 'in_progress',
                            'can_edit' => in_array($visit->visit_status, ['pending', 'in_progress']),
                        ]
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Today\'s visits retrieved successfully',
                'data' => [
                    'visits' => $visits,
                    'summary' => [
                        'total' => $visits->count(),
                        'pending' => $visits->where('visit_status', 'pending')->count(),
                        'in_progress' => $visits->where('visit_status', 'in_progress')->count(),
                        'completed' => $visits->where('visit_status', 'completed')->count(),
                        'cancelled' => $visits->where('visit_status', 'cancelled')->count()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving visits: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start a store visit
     */
    public function startVisit(Request $request, $visitId)
    {
        $validator = Validator::make(array_merge($request->all(), ['visit_id' => $visitId]), [
            'visit_id' => 'required|exists:store_visits,id',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
            
            if (!$salesRep) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sales representative not found'
                ], 404);
            }

            $visit = StoreVisit::where('id', $visitId)
                ->where('sales_rep_id', $salesRep->id)
                ->first();

            if (!$visit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Visit not found or unauthorized'
                ], 404);
            }

            if ($visit->visit_status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Visit cannot be started. Current status: ' . $visit->visit_status
                ], 400);
            }

            // Update visit status
            $visit->update([
                'visit_status' => 'in_progress',
                'check_in_time' => now(),
                'notes' => $request->notes ? ($visit->notes . "\n" . $request->notes) : $visit->notes
            ]);

            // Update sales rep location if provided
            if ($request->has('latitude') && $request->has('longitude')) {
                $salesRep->updateLocationFromRequest(
                    $request->latitude,
                    $request->longitude
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Visit started successfully',
                'data' => [
                    'visit_id' => $visit->id,
                    'status' => $visit->visit_status,
                    'check_in_time' => $visit->check_in_time->format('H:i'),
                    'store_name' => $visit->retailStore->name ?? 'Unknown Store'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error starting visit: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete a store visit
     */
    public function completeVisit(Request $request, $visitId)
    {
        $validator = Validator::make(array_merge($request->all(), ['visit_id' => $visitId]), [
            'visit_id' => 'required|exists:store_visits,id',
            'notes' => 'nullable|string|max:2000',
            'order_amount' => 'nullable|numeric|min:0',
            'outcome' => 'nullable|string|in:successful,unsuccessful,rescheduled',
            'next_visit_date' => 'nullable|date|after:today',
            'feedback' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
            
            if (!$salesRep) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sales representative not found'
                ], 404);
            }

            $visit = StoreVisit::where('id', $visitId)
                ->where('sales_rep_id', $salesRep->id)
                ->first();

            if (!$visit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Visit not found or unauthorized'
                ], 404);
            }

            if ($visit->visit_status !== 'in_progress') {
                return response()->json([
                    'success' => false,
                    'message' => 'Visit cannot be completed. Current status: ' . $visit->visit_status
                ], 400);
            }

            // Update visit
            $updateData = [
                'visit_status' => 'completed',
                'check_out_time' => now(),
                'order_amount' => $request->order_amount ?? $visit->order_amount,
                'feedback' => $request->feedback,
                'next_visit_date' => $request->next_visit_date
            ];

            if ($request->notes) {
                $updateData['notes'] = $visit->notes ? ($visit->notes . "\n" . $request->notes) : $request->notes;
            }

            $visit->update($updateData);

            // Update sales rep stats
            $salesRep->increment('successful_visits');
            $salesRep->increment('total_visits');

            return response()->json([
                'success' => true,
                'message' => 'Visit completed successfully',
                'data' => [
                    'visit_id' => $visit->id,
                    'status' => $visit->visit_status,
                    'check_out_time' => $visit->check_out_time->format('H:i'),
                    'duration_minutes' => $visit->check_in_time && $visit->check_out_time 
                        ? $visit->check_in_time->diffInMinutes($visit->check_out_time) 
                        : null,
                    'order_amount' => (float) $visit->order_amount
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error completing visit: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload visit photos
     */
    public function uploadVisitPhotos(Request $request, $visitId)
    {
        $validator = Validator::make(array_merge($request->all(), ['visit_id' => $visitId]), [
            'visit_id' => 'required|exists:store_visits,id',
            'photos' => 'required|array|max:10',
            'photos.*' => 'image|mimes:jpeg,png,jpg|max:5120' // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
            
            if (!$salesRep) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sales representative not found'
                ], 404);
            }

            $visit = StoreVisit::where('id', $visitId)
                ->where('sales_rep_id', $salesRep->id)
                ->first();

            if (!$visit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Visit not found or unauthorized'
                ], 404);
            }

            $existingPhotos = $visit->photos ?? [];
            $newPhotos = [];

            foreach ($request->file('photos') as $photo) {
                $filename = 'visit_' . $visitId . '_' . time() . '_' . uniqid() . '.' . $photo->extension();
                $path = $photo->storeAs('visit_photos', $filename, 'public');
                $newPhotos[] = $path;
            }

            $allPhotos = array_merge($existingPhotos, $newPhotos);
            $visit->update(['photos' => $allPhotos]);

            return response()->json([
                'success' => true,
                'message' => 'Photos uploaded successfully',
                'data' => [
                    'visit_id' => $visit->id,
                    'uploaded_photos' => array_map(function($photo) {
                        return Storage::url($photo);
                    }, $newPhotos),
                    'total_photos' => count($allPhotos)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error uploading photos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update FCM token for push notifications
     */
    public function updateFcmToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fcm_token' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
            
            if (!$salesRep) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sales representative not found'
                ], 404);
            }

            $salesRep->update(['fcm_token' => $request->fcm_token]);

            return response()->json([
                'success' => true,
                'message' => 'FCM token updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating FCM token: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get performance analytics
     */
    public function getAnalytics(Request $request)
    {
        try {
            $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
            
            if (!$salesRep) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sales representative not found'
                ], 404);
            }

            $period = $request->get('period', 'monthly'); // daily, weekly, monthly, yearly
            $metrics = $salesRep->getPerformanceMetrics($period);
            
            // Target achievements
            $salesTarget = $salesRep->getTargetAchievement('sales', $period);
            $visitsTarget = $salesRep->getTargetAchievement('visits', $period);
            
            return response()->json([
                'success' => true,
                'message' => 'Analytics retrieved successfully',
                'data' => [
                    'period' => $period,
                    'metrics' => $metrics,
                    'targets' => [
                        'sales' => $salesTarget,
                        'visits' => $visitsTarget
                    ],
                    'commission_summary' => $salesRep->getMonthlyCommissionSummary()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync offline data
     */
    public function syncOfflineData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sync_data' => 'required|array',
            'sync_data.*.type' => 'required|in:location,visit_start,visit_complete,photo_upload',
            'sync_data.*.data' => 'required|array',
            'sync_data.*.timestamp' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
            
            if (!$salesRep) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sales representative not found'
                ], 404);
            }

            $successCount = 0;
            $errors = [];

            foreach ($request->sync_data as $index => $syncItem) {
                try {
                    switch ($syncItem['type']) {
                        case 'location':
                            if (isset($syncItem['data']['latitude']) && isset($syncItem['data']['longitude'])) {
                                $salesRep->updateLocationFromRequest(
                                    $syncItem['data']['latitude'],
                                    $syncItem['data']['longitude'],
                                    $syncItem['data']['device_info'] ?? null
                                );
                                $successCount++;
                            }
                            break;
                            
                        case 'visit_start':
                            if (isset($syncItem['data']['visit_id'])) {
                                $visit = StoreVisit::where('id', $syncItem['data']['visit_id'])
                                    ->where('sales_rep_id', $salesRep->id)
                                    ->first();
                                if ($visit && $visit->visit_status === 'pending') {
                                    $visit->update([
                                        'visit_status' => 'in_progress',
                                        'check_in_time' => $syncItem['timestamp']
                                    ]);
                                    $successCount++;
                                }
                            }
                            break;
                            
                        case 'visit_complete':
                            if (isset($syncItem['data']['visit_id'])) {
                                $visit = StoreVisit::where('id', $syncItem['data']['visit_id'])
                                    ->where('sales_rep_id', $salesRep->id)
                                    ->first();
                                if ($visit && $visit->visit_status === 'in_progress') {
                                    $visit->update([
                                        'visit_status' => 'completed',
                                        'check_out_time' => $syncItem['timestamp'],
                                        'notes' => $syncItem['data']['notes'] ?? $visit->notes,
                                        'order_amount' => $syncItem['data']['order_amount'] ?? $visit->order_amount
                                    ]);
                                    $successCount++;
                                }
                            }
                            break;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Item $index: " . $e->getMessage();
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Sync completed. $successCount items processed successfully.",
                'data' => [
                    'synced_count' => $successCount,
                    'total_count' => count($request->sync_data),
                    'errors' => $errors
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error syncing data: ' . $e->getMessage()
            ], 500);
        }
    }
}