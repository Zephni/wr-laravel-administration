<?php

namespace WebRegulate\LaravelAdministration\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

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
     * Attempt login
     *
     * @param string $identifier
     * @param string $password
     * @param bool $remember
     * @return true
     */
    public static function attemptLogin(string $identifier, string $password, bool $remember = true)
    {
        $success = Auth::attempt([
            'email' => $identifier,
            'password' => $password
        ], $remember);

        return $success;
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
        // If user_avatar callback is set in config, use that
        $userAvatarConfig = config('wr-laravel-administration.user_avatar');
        if ($userAvatarConfig != null && is_callable($userAvatarConfig)) {
            return $userAvatarConfig($this->toFrontendUser());
        }

        // If has avatar, return it
        $avatar = $this->wrlaUserData?->avatar;
        if (!empty($avatar)) {
            return '/'.ltrim("storage/images/avatars/$avatar", '/');
        }

        // If name is empty, use U
        $fullName = $this->getFullName();
        $fullName = !empty($this->fullName) ? $this->fullName : 'U';

        // Get just the first characters of all the words
        $name = preg_replace('/\b(\w)|./', '$1', $fullName);

        // Otherwise return default
        return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&color=EBF4FF&background=00BFA0&size=128&font-size=0.5&rounded=true';
    }

    /**
     * Get full name
     *
     * @return string
     */
    public function getFullName(): string
    {
        return $this->name;
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

            return User::find($user->id)->first();
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
        return data_get(json_decode($wrlaUserData->permissions ?? '', true), $dottedKey);
    }

    /**
     * Set inner permission by key
     * @param string $dottedKey
     * @param mixed $value
     * @return void
     */
    public function setPermission(string $dottedKey, $value): void
    {
        $wrlaUserData = $this->wrlaUserData;
        if ($wrlaUserData == null) return;
        $permissions = json_decode($wrlaUserData->permissions ?? '', true);

        // If not array, create new array
        if (!is_array($permissions)) {
            $permissions = [];
        }

        data_set($permissions, $dottedKey, $value);
        $wrlaUserData->permissions = json_encode($permissions);
        $wrlaUserData->save();
    }

    /**
     * Get user setting based on dotted key value, such as 'ui.theme'
     * @return mixed
     */
    public function getSetting(string $dottedKey): mixed
    {
        $wrlaUserData = $this->wrlaUserData;
        if ($wrlaUserData == null) return null;
        return data_get(json_decode($wrlaUserData->settings ?? '', true), $dottedKey);
    }

    /**
     * Set inner setting by key
     * @param string $dottedKey
     * @param mixed $value
     * @return void
     */
    public function setSetting(string $dottedKey, $value): void
    {
        $wrlaUserData = $this->wrlaUserData;
        if ($wrlaUserData == null) return;
        $settings = json_decode($wrlaUserData->settings ?? '', true);

        // If not array, create new array
        if (!is_array($settings)) {
            $settings = [];
        }

        data_set($settings, $dottedKey, $value);
        $wrlaUserData->settings = json_encode($settings);
        $wrlaUserData->save();
    }

    /**
     * Get user data based on dotted key value, such as 'profile.avatar'
     * @return mixed
     */
    public function getData(string $dottedKey): mixed
    {
        $wrlaUserData = $this->wrlaUserData;
        if ($wrlaUserData == null) return null;
        return data_get(json_decode($wrlaUserData->data ?? '', true), $dottedKey);
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

        // If not array, create new array
        if (!is_array($data)) {
            $data = [];
        }

        data_set($data, $dottedKey, $value);
        $wrlaUserData->data = json_encode($data);
        $wrlaUserData->save();
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
