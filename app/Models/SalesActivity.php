<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;

class SalesActivity extends Model
{
    use PreventDemoModeChanges;

    protected $fillable = [
        'sales_rep_id',
        'customer_id',
        'activity_type',
        'subject',
        'description',
        'activity_date',
        'follow_up_date',
        'status',
        'priority',
        'outcome',
        'notes'
    ];

    protected $casts = [
        'activity_date' => 'datetime',
        'follow_up_date' => 'datetime'
    ];

    const ACTIVITY_TYPES = [
        'call' => 'Phone Call',
        'email' => 'Email',
        'meeting' => 'Meeting',
        'demo' => 'Product Demo',
        'follow_up' => 'Follow Up',
        'proposal' => 'Proposal',
        'other' => 'Other'
    ];

    const ACTIVITY_STATUS = [
        'scheduled' => 'Scheduled',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled'
    ];

    const PRIORITIES = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent'
    ];

    const OUTCOMES = [
        'positive' => 'Positive',
        'neutral' => 'Neutral',
        'negative' => 'Negative',
        'no_contact' => 'No Contact'
    ];

    public function salesRepresentative()
    {
        return $this->belongsTo(SalesRepresentative::class, 'sales_rep_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // Check if activity is completed
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    // Check if activity is overdue
    public function isOverdue()
    {
        return $this->activity_date < now() && $this->status !== 'completed';
    }

    // Check if follow up is due
    public function isFollowUpDue()
    {
        return $this->follow_up_date && 
               $this->follow_up_date <= now() && 
               $this->status === 'completed';
    }

    // Get activity type name
    public function getActivityTypeNameAttribute()
    {
        return self::ACTIVITY_TYPES[$this->activity_type] ?? $this->activity_type;
    }

    // Get status name
    public function getStatusNameAttribute()
    {
        return self::ACTIVITY_STATUS[$this->status] ?? $this->status;
    }

    // Get priority name
    public function getPriorityNameAttribute()
    {
        return self::PRIORITIES[$this->priority] ?? $this->priority;
    }

    // Get outcome name
    public function getOutcomeNameAttribute()
    {
        return self::OUTCOMES[$this->outcome] ?? $this->outcome;
    }
}
