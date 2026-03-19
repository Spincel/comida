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
        'parent_id',
    ];

    /**
     * Get the parent area.
     */
    public function parent()
    {
        return $this->belongsTo(Area::class, 'parent_id');
    }

    /**
     * Get the child areas.
     */
    public function children()
    {
        return $this->hasMany(Area::class, 'parent_id');
    }

    /**
     * Get the full hierarchical path name.
     */
    public function getFullPathAttribute()
    {
        if ($this->parent) {
            return $this->parent->full_path . ' > ' . $this->name;
        }
        return $this->name;
    }

    /**
     * Get total users in this area and all its descendants recursively.
     */
    public function getTotalBranchUsersAttribute()
    {
        // Simple recursion, but we'll call it only when needed manually
        $count = $this->users()->count();
        foreach ($this->children as $child) {
            $count += $child->total_branch_users;
        }
        return $count;
    }

    protected $appends = []; // Removed full_path and total_branch_users from auto-loading

    /**
     * Get IDs of all descendant areas recursively.
     */
    public function getAllChildrenIds()
    {
        $ids = [$this->id];
        foreach ($this->children as $child) {
            $ids = array_merge($ids, $child->getAllChildrenIds());
        }
        return $ids;
    }

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
