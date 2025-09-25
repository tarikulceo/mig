<?php

namespace App\Models;

use App;
use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;

class Product extends Model
{
    use PreventDemoModeChanges;
    
    protected $guarded = ['choice_attributes'];

    protected $with = ['product_translations', 'taxes', 'thumbnail'];

    protected $casts = [
        'sales_commission_percentage' => 'decimal:2',
        'enable_sales_commission' => 'boolean',
    ];

    public function getTranslation($field = '', $lang = false)
    {
        $lang = $lang == false ? App::getLocale() : $lang;
        $product_translations = $this->product_translations->where('lang', $lang)->first();
        return $product_translations != null ? $product_translations->$field : $this->$field;
    }

    public function product_translations()
    {
        return $this->hasMany(ProductTranslation::class);
    }

    public function main_category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
    
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'product_categories');
    }

    public function frequently_bought_products()
    {
        return $this->hasMany(FrequentlyBoughtProduct::class);
    }

    public function product_categories()
    {
        return $this->hasMany(ProductCategory::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function product_queries()
    {
        return $this->hasMany(ProductQuery::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function stocks()
    {
        return $this->hasMany(ProductStock::class);
    }

    public function taxes()
    {
        return $this->hasMany(ProductTax::class);
    }

    public function flash_deal_products()
    {
        return $this->hasMany(FlashDealProduct::class);
    }

    public function bids()
    {
        return $this->hasMany(AuctionProductBid::class);
    }

    public function thumbnail()
    {
        return $this->belongsTo(Upload::class, 'thumbnail_img');
    }

    public function scopePhysical($query)
    {
        return $query->where('digital', 0);
    }

    public function scopeDigital($query)
    {
        return $query->where('digital', 1);
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }
    
    public function scopeIsApprovedPublished($query)
    {
        return $query->where('approved', '1')->where('published', 1);
    }

    public function last_viewed_products()
    {
        return $this->hasMany(LastViewedProduct::class);
    }

    public function warranty()
    {
        return $this->belongsTo(Warranty::class);
    }

    public function warrantyNote()
    {
        return $this->belongsTo(Note::class, 'warranty_note_id');
    }

    public function refundNote()
    {
        return $this->belongsTo(Note::class, 'refund_note_id');
    }

    public function warehouse_stocks()
    {
        return $this->hasMany(WarehouseStock::class);
    }

    public function getStockByWarehouse($warehouse_id)
    {
        return $this->warehouse_stocks()->where('warehouse_id', $warehouse_id)->first();
    }

    public function getTotalStock()
    {
        return $this->warehouse_stocks()->sum('quantity');
    }

    public function getTotalAvailableStock()
    {
        return $this->warehouse_stocks()->sum(\DB::raw('quantity - reserved_quantity'));
    }

    public function getGlobalLowStockThreshold()
    {
        // Use the product's low_stock_quantity for global threshold
        return $this->low_stock_quantity ?: 10; // Default to 10 if no threshold set
    }

    public function isGlobalLowStock()
    {
        $totalStock = $this->getTotalStock();
        $threshold = $this->getGlobalLowStockThreshold();
        return $totalStock <= $threshold;
    }

    public function getFormattedNameWithVariant($productStockId = null)
    {
        $name = $this->getTranslation('name');
        
        if ($productStockId) {
            $productStock = $this->stocks()->find($productStockId);
            if ($productStock && !empty($productStock->variant)) {
                return $name . ' (' . $productStock->variant . ')';
            }
        }
        
        return $name;
    }

    public function getCurrentStockStatus()
    {
        $totalStock = $this->getTotalStock();
        
        if ($totalStock <= 0) {
            return [
                'status' => 'out_of_stock',
                'text' => 'Out of Stock',
                'badge_class' => 'badge-secondary'
            ];
        } elseif ($this->isGlobalLowStock()) {
            return [
                'status' => 'low_stock',
                'text' => 'Low Stock',
                'badge_class' => 'badge-warning'
            ];
        } else {
            return [
                'status' => 'in_stock',
                'text' => 'In Stock',
                'badge_class' => 'badge-success'
            ];
        }
    }

    /**
     * Check if this product is eligible for sales commission
     * Only in-house products can have sales commission
     */
    public function isEligibleForSalesCommission()
    {
        return $this->added_by === 'admin' && $this->enable_sales_commission;
    }

    /**
     * Calculate sales commission amount for given order value
     */
    public function calculateSalesCommission($orderValue)
    {
        if (!$this->isEligibleForSalesCommission()) {
            return 0;
        }

        return ($orderValue * $this->sales_commission_percentage) / 100;
    }

    /**
     * Get formatted sales commission percentage
     */
    public function getFormattedCommissionPercentage()
    {
        return $this->sales_commission_percentage ? $this->sales_commission_percentage . '%' : 'N/A';
    }

}
