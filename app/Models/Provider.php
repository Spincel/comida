<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'contact_person',
        'contact_phone',
        'contact_email',
        'delivery_time_window',
    ];

    /**
     * Set the provider's name.
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = mb_strtoupper($value, 'UTF-8');
    }

    /**
     * Get the daily menus for the provider.
     */
    public function dailyMenus()
    {
        return $this->hasMany(DailyMenu::class);
    }
}
