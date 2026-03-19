<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AreaSessionStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_daily_status_id',
        'area_id',
        'evidence_image',
        'status',
    ];

    protected $appends = ['evidence_url'];

    public function getEvidenceUrlAttribute()
    {
        return $this->evidence_image ? asset('storage/' . $this->evidence_image) : null;
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function session()
    {
        return $this->belongsTo(ProviderDailyStatus::class, 'provider_daily_status_id');
    }
}
