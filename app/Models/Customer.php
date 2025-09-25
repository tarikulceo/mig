<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;

class Customer extends Model
{
    use PreventDemoModeChanges;

    protected $fillable = [
        'user_id',
        'sales_rep_id',
        'territory_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function salesRepresentative()
    {
        return $this->belongsTo(SalesRepresentative::class, 'sales_rep_id');
    }

    public function territory()
    {
        return $this->belongsTo(SalesTerritory::class, 'territory_id');
    }
}
