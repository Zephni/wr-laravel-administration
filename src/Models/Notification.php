<?php

namespace WebRegulate\LaravelAdministration\Models;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Classes\NotificationBase;

class Notification extends Model
{
    protected $table = 'wrla_notifications';

    protected $fillable = [
        'type',
        'user_id',
        'data',
        'read_at',
    ];

    /**
     * Make a new notification.
     * 
     * @param string $notificationDefinitionClass
     * @param string $userId
     * @param array $data
     * @return Notification
     */
    public static function make(string $notificationDefinitionClass, string $userId, array $data): Notification
    {
        $notification = Notification::create([
            'type' => $notificationDefinitionClass,
            'user_id' => $userId,
            'read_at' => null,
            'data' => json_encode($data),
        ]);

        $notification->getDefinition()->postCreated();

        return $notification;
    }

    /**
     * Get notification definition instance.
     * 
     * @return NotificationBase
     */
    public function getDefinition(): NotificationBase
    {
        // Now we use cache instead
        return cache()->remember("notification.{$this->id}.definition", now()->addMinutes(5), function() {
            $notificationClass = $this->type;
    
            // If doesn't start with \, prepend it
            if (str_starts_with($notificationClass, '\\') === false) {
                $notificationClass = '\\' . $notificationClass;
            }
    
            return new $notificationClass($this->user_id, json_decode($this->data, true));
        });
    }

    public function getFinalButtons(): Collection
    {
        $definition = $this->getDefinition();
        return $definition->getButtons($this->defaultButtons(), $this);
    }

    private function defaultButtons(): Collection
    {
        return collect([
            NotificationBase::staticBuildNotificationActionButton(
                $this,
                'flipNotificationMarkedAsRead',
                [$this->id],
                $this->read_at === null ? 'Read' : 'Undo',
                $this->read_at === null ? 'fa fa-check' : 'fa fa-undo',
                [
                    'title' => $this->read_at === null ? 'Mark as read' : 'Mark as unread',
                ]
            )
        ]);
    }

    /**
     * Mark notification as read.
     *
     * @return void
     */
    public function markAsRead()
    {
        if (User::current() === null) {
            return;
        }

        $this->read_at = now();
        $this->save();
    }

    /**
     * Flip notification as read.
     * 
     * @return void
     */
    public function flipMarkedAsRead(): void
    {
        $this->read_at = $this->read_at === null ? now() : null;
        $this->save();
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
