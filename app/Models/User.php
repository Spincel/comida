<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name', // Keep for backward compatibility where needed directly, but prefer first_name + last_name
        'first_name',
        'last_name',
        'second_last_name',
        'employee_number',
        'username',
        'email',
        'avatar',
        'password',
        'area_id',
        'role',
        'status',
        'theme_settings',
    ];

    /**
     * Get the user's full name.
     */
    public function getNameAttribute($value)
    {
        if ($this->first_name) {
            $parts = array_filter([$this->first_name, $this->last_name, $this->second_last_name]);
            return implode(' ', $parts);
        }
        return $value; // Fallback to old 'name' column if not split
    }

    /**
     * Get the user's avatar URL.
     */
    public function getAvatarUrlAttribute()
    {
        $displayName = $this->name ?? 'User';
        return $this->avatar 
            ? asset('storage/' . $this->avatar) 
            : 'https://ui-avatars.com/api/?name=' . urlencode($displayName) . '&color=7F9CF5&background=EBF4FF';
    }

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = ['name', 'avatar_url'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'theme_settings' => 'array',
        ];
    }

    /**
     * Get the area that the user belongs to.
     */
    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    /**
     * Get the orders for the user.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the session authorizations for the user.
     */
    public function sessionAuthorizations()
    {
        return $this->hasMany(SessionAuthorization::class);
    }
}
