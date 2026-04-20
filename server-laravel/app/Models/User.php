<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'role',
        'glpi_user_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

    /**
     * Relación con el Rol.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Comprueba si el usuario tiene un permiso determinado.
     */
    public function hasPermission(string $permissionSlug): bool
    {
        if (!$this->role) return false;
        return $this->role->permissions()->where('slug', $permissionSlug)->exists();
    }

    /**
     * Comprueba si el usuario tiene un rol determinado.
     */
    public function hasRole(string $roleSlug): bool
    {
        if (is_string($this->role)) {
            return $this->role === $roleSlug;
        }
        return $this->role?->slug === $roleSlug;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }
}

