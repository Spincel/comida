<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'manager_id',
    ];

    /**
     * Get the users for the area.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the manager for the area.
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
}
