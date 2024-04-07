<?php

namespace App\WRLA;

use \Illuminate\Database\Eloquent\Model;
use \Illuminate\Support\Arr;

class User extends Model
{
    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'permissions',
        'settings',
        'data'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /* Static methods
    --------------------------------------------- */
    /**
     * Get current authenticated user
     * @return User|null
     */
    public static function current($allowCache = false): ?User
    {
        $user = auth()->user();

        if ($user == null) {
            return null;
        }

        if ($allowCache) {
            return once(function() use ($user) {
                return User::find($user->id);
            });
        }
        
        return User::find($user->id);
    }

    /**
     * Get user by email
     * @param string $email
     * @return User|null
     */
    public static function getByEmail(string $email): ?User
    {
        return self::where('email', $email)->first();
    }

    /* Public methods
    --------------------------------------------- */
    /**
     * Get user permission, such as 'layered.permission.key'
     * @return mixed
     */
    public function getPermission(string $dottedKey): mixed
    {
        return Arr::get(json_decode($this->permissions, true), $dottedKey);
    }

    /**
     * Get user setting based on dotted key value, such as 'ui.theme'
     * @return mixed
     */
    public function getSetting(string $dottedKey): mixed
    {
        return Arr::get(json_decode($this->settings, true), $dottedKey);
    }

    /**
     * Get user data based on dotted key value, such as 'profile.avatar'
     * @return mixed
     */
    public function getData(string $dottedKey): mixed
    {
        return Arr::get(json_decode($this->data, true), $dottedKey);
    }

    /**
     * Is user an admin
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->getPermission('admin') == true;
    }
}