<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SessionAuthorization extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_daily_status_id',
        'user_id',
        'authorized_by_user_id',
    ];

    public function session()
    {
        return $this->belongsTo(ProviderDailyStatus::class, 'provider_daily_status_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function authorizedBy()
    {
        return $this->belongsTo(User::class, 'authorized_by_user_id');
    }
}
