<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;

class WholesalePrice extends Model
{
    use HasFactory, PreventDemoModeChanges;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'product_stock_id',
        'min_qty',
        'max_qty',
        'price',
    ];
}
