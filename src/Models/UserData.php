<?php
namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class UserData extends Model
{
    protected $connection = 'mysql';
    protected $table = 'wrla_user_data';
    public $timestamps = false;
    protected $fillable = [
        'user_id',
        'permissions',
        'settings',
        'data',
    ];

    /**
     * Get current logged in user
     *
     * @return mixed
     */
    public static function getCurrentUser()
    {
        return Auth::user();
    }

    /**
     * Get current logged in user -> user data
     *
     * @return mixed
     */
    public static function getCurrentUserData()
    {
        return once(fn() => UserData::where('user_id', UserData::getCurrentUser()?->id)?->first());
    }

    /**
     * Attach user to the model
     *
     * @param mixed $user
     * @return static
     */
    public function attachUser(mixed $user): static
    {
        $this->user = $user;
        $this->user_id = $user->id;
        return $this;
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
            return $userAvatarConfig($this->user);
        }

        // If has avatar, return it
        $avatar = $this->avatar;
        if (!empty($avatar)) {
            return '/'.ltrim("storage/images/avatars/$avatar", '/');
        }

        // Get full name, if empty use 'U'
        $fullName = $this->getFullName();
        $fullName = !empty($fullName) ? $fullName : 'U';

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
        return $this->user?->name ?? 'User';
    }

    /* Static methods
    --------------------------------------------- */
    /**
     * Get user by email
     * @param string $email
     * @return mixed
     */
    public static function getByEmail(string $email): mixed
    {
        return WRLAHelper::getUserModelClass()::where('email', $email)->first();
    }

    /* Public methods
    --------------------------------------------- */
    /**
     * Get user permission, such as 'layered.permission.key'
     * @return mixed
     */
    public function getPermission(string $dottedKey): mixed
    {
        return data_get(json_decode($this->permissions ?? '', true), $dottedKey);
    }

    /**
     * Set inner permission by key
     * @param string $dottedKey
     * @param mixed $value
     * @return void
     */
    public function setPermission(string $dottedKey, $value): void
    {
        $permissions = json_decode($this->permissions ?? '', true);

        // If not array, create new array
        if (!is_array($permissions)) {
            $permissions = [];
        }

        data_set($permissions, $dottedKey, $value);
        $this->permissions = json_encode($permissions);
        $this->save();
    }

    /**
     * Get user setting based on dotted key value, such as 'ui.theme'
     * @return mixed
     */
    public function getSetting(string $dottedKey): mixed
    {
        return data_get(json_decode($this->settings ?? '', true), $dottedKey);
    }

    /**
     * Set inner setting by key
     * @param string $dottedKey
     * @param mixed $value
     * @return void
     */
    public function setSetting(string $dottedKey, $value): void
    {
        $settings = json_decode($this->settings ?? '', true);

        // If not array, create new array
        if (!is_array($settings)) {
            $settings = [];
        }

        data_set($settings, $dottedKey, $value);
        $this->settings = json_encode($settings);
        $this->save();
    }

    /**
     * Get user data based on dotted key value, such as 'profile.avatar'
     * @return mixed
     */
    public function getData(string $dottedKey): mixed
    {
        return data_get(json_decode($this->data ?? '', true), $dottedKey);
    }

    /**
     * Set inner data by key
     * @param string $dottedKey
     * @param mixed $value
     * @return void
     */
    public function setData(string $dottedKey, $value): void
    {
        $data = json_decode($this->data ?? '', true);

        // If not array, create new array
        if (!is_array($data)) {
            $data = [];
        }

        data_set($data, $dottedKey, $value);
        $this->data = json_encode($data);
        $this->save();
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

    // Relationships
    public function user()
    {
        return $this->belongsTo(WRLAHelper::getUserModelClass());
    }
}
