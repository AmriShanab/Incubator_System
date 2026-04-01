<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser; // 1. Add this
use Filament\Panel; // 2. Add this
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser // 3. Add 'implements FilamentUser'
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * 4. This method controls who can log in to the admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // For now, let everyone with a login account in.
        // On a real production site, you might use: 
        // return str_ends_with($this->email, '@yourdomain.com');
        return true; 
    }
}