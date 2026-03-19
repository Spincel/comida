<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyMenu extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'available_on',
        'status', 
        'provider_id',
        'ai_source_image',
    ];

    /**
     * Get the provider that owns the daily menu.
     */
    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * Get the orders for the daily menu.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
