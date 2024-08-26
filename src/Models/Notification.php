<?php

namespace WebRegulate\LaravelAdministration\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use WebRegulate\LaravelAdministration\Classes\NotificationBase;

class Notification extends Model
{
    protected $fillable = [
        'user_ids',
        'unread_user_ids',
        'data',
        'type',
    ];

    /**
     * Make a new notification.
     * 
     * @param string $notificationDefinitionClass
     * @param array $userIds
     * @param array $data
     * @return Notification
     */
    public static function make(string $notificationDefinitionClass, array $userIds, array $data): Notification
    {
        return Notification::create([
            'type' => $notificationDefinitionClass,
            'user_ids' => implode(',', $userIds),
            'unread_user_ids' => implode(',', $userIds),
            'data' => json_encode($data),
        ]);
    }

    /**
     * Get notification definition instance.
     * 
     * @return NotificationBase
     */
    public function getDefinition(): NotificationBase
    {
        return new $this->type(User::current(), json_decode($this->data, true));
    }

    /**
     * Mark notification as read for a user.
     *
     * @param int $userId
     * @return void
     */
    public function markAsRead(?int $userId)
    {
        if (!$userId) {
            $userId = auth()->id();
        }

        $unreadUserIds = json_decode($this->unread_user_ids);
        $key = array_search($userId, $unreadUserIds);
        if ($key !== false) {
            unset($unreadUserIds[$key]);
            $this->unread_user_ids = json_encode($unreadUserIds);
            $this->save();
        }
    }
}
