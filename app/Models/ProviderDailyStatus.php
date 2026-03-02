<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderDailyStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'date',
        'meal_type',
        'status',
        'activated_at',
        'selected_area_ids', // Add to fillable
    ];

    protected $casts = [
        'selected_area_ids' => 'array', // Cast as array
        'activated_at' => 'datetime',
    ];

    /**
     * Get the provider that owns the daily status.
     */
    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * Get the authorizations for the session.
     */
    public function authorizations()
    {
        return $this->hasMany(SessionAuthorization::class, 'provider_daily_status_id');
    }

    /**
     * Get the authorized users for the session.
     */
    public function authorizedUsers()
    {
        return $this->belongsToMany(User::class, 'session_authorizations', 'provider_daily_status_id', 'user_id');
    }
}
