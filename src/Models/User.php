<?php

namespace WebRegulate\LaravelAdministration\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use \Illuminate\Support\Arr;

class User extends Authenticatable implements CanResetPassword
{
    use HasFactory, Notifiable, SoftDeletes;

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

    /**
     * Implement method 'getEmailForPasswordReset' from 'CanResetPassword' interface
     *
     * @return string
     */
    public function getEmailForPasswordReset(): string
    {
        return $this->email;
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \WebRegulate\LaravelAdministration\Notifications\WRLAResetPasswordNotification($this->email, $token));
    }

    /**
     * Get profile image
     *
     * @return string
     */
    public function getProfileAvatar(): string {
        // If data has image, return it
        $avatar = $this->getData('profile.avatar');
        if (!empty($avatar)) {
            // Return if image exists
            if (file_exists(public_path($avatar))) {
                return '/'.ltrim($avatar, '/');
            }
        }

        // If name is empty, use U
        $name = !empty($this->name) ? $this->name : 'U';

        // Get just the first characters of all the words
        $name = preg_replace('/\b(\w)|./', '$1', $name);

        // Otherwise return default
        return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&color=EBF4FF&background=00BFA0&size=128&font-size=0.5&rounded=true';
    }

    /* Static methods
    --------------------------------------------- */
    /**
     * Get current authenticated user
     * @return User|null
     */
    public static function current($allowCache = true): ?User
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
     * Get role
     * 
     * @return string
     */
    public function getRole(): string
    {
        if($this->isMaster()) {
            return 'Master Administrator';
        } else if($this->isAdmin()) {
            return 'Administrator';
        } else {
            return 'User';
        }
    }

    /**
     * Get current theme
     * @return string
     */
    public function getCurrentThemeKey(): ?string
    {
        return $this->getSetting('theme.current');
    }

    /**
     * Is user a master
     * @return bool
     */
    public function isMaster(): bool
    {
        return $this->getPermission('master') == true;
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
