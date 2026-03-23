<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'daily_menu_id',
        'meal_type',
        'preferences',
        'activity_performed',
        'status',
    ];

    protected $casts = [
        'preferences' => 'array',
    ];

    public function dailyMenu()
    {
        return $this->belongsTo(DailyMenu::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
