<?php

namespace WebRegulate\LaravelAdministration\Models;

use \Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

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

    protected $with = ['wrlaUserData'];

    /**
     * From User model
     *
     * @param \App\Models\User $user
     * @return static
     */
    public static function fromUser(\App\Models\User $user): static
    {
        // Build WRLA user from frontend user (set attributes)
        $wrlaUser = new static();
        foreach ($user->getAttributes() as $key => $value) {
            $wrlaUser->$key = $value;
        }

        // Set password
        $wrlaUser->password = $user->password;

        return $wrlaUser;
    }

    /**
     * To User model
     *
     * @return \App\Models\User
     */
    public function toFrontendUser(): \App\Models\User
    {
        // Build frontend user from WRLA user (set attributes)
        $user = new \App\Models\User();
        foreach ($this->getAttributes() as $key => $value) {
            $user->$key = $value;
        }

        // Set password
        $user->password = $this->password;

        return $user;
    }

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
     * @param string $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \WebRegulate\LaravelAdministration\Classes\WRLAResetPasswordNotification($this->email, $token));
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
            // if (file_exists(storage_path("storage/images/avatars/$avatar"))) {
                return '/'.ltrim("storage/images/avatars/$avatar", '/');
            // }
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
        if ($allowCache) {
            return once(function() {
                $user = Auth::user();

                if ($user == null) {
                    return null;
                }

                return User::find($user->id);
            });
        }
        else
        {
            $user = Auth::user();

            if ($user == null) {
                return null;
            }

            return User::find($user->id);
        }
    }

    /**
     * Get user by email
     * @param string $email
     * @return User|null
     */
    public static function getByEmail(string $email): ?User
    {
        return static::where('email', $email)->first();
    }

    /* Public methods
    --------------------------------------------- */
    /**
     * Get user permission, such as 'layered.permission.key'
     * @return mixed
     */
    public function getPermission(string $dottedKey): mixed
    {
        $wrlaUserData = $this->wrlaUserData;
        if ($wrlaUserData == null) return null;
        return Arr::get(json_decode($wrlaUserData->permissions ?? '', true), $dottedKey);
    }

    /**
     * Get user setting based on dotted key value, such as 'ui.theme'
     * @return mixed
     */
    public function getSetting(string $dottedKey): mixed
    {
        $wrlaUserData = $this->wrlaUserData;
        if ($wrlaUserData == null) return null;
        return Arr::get(json_decode($wrlaUserData->settings ?? '', true), $dottedKey);
    }

    /**
     * Get user data based on dotted key value, such as 'profile.avatar'
     * @return mixed
     */
    public function getData(string $dottedKey): mixed
    {
        $wrlaUserData = $this->wrlaUserData;
        if ($wrlaUserData == null) return null;
        return Arr::get(json_decode($wrlaUserData->data ?? '', true), $dottedKey);
    }

    /**
     * Set inner data by key
     * @param string $dottedKey
     * @param mixed $value
     * @return void
     */
    public function setData(string $dottedKey, $value): void
    {
        $wrlaUserData = $this->wrlaUserData;
        if ($wrlaUserData == null) return;
        $data = json_decode($wrlaUserData->data ?? '', true);
        Arr::set($data, $dottedKey, $value);
        $wrlaUserData->data = json_encode($data);
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

    /**
     * WRLA User data relationship
     *
     * @return HasOne
     */
    public function wrlaUserData(): HasOne
    {
        return $this->hasOne(UserData::class, 'user_id');
    }
}