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
        $notificationClass = $this->type;

        // If doesn't start with \, prepend it
        if (str_starts_with($notificationClass, '\\') === false) {
            $notificationClass = '\\' . $notificationClass;
        }

        return new $notificationClass($this->user_id, json_decode($this->data, true));
    }

    public function getFinalButtons(): Collection
    {
        $definition = $this->getDefinition();
        return $definition->getButtons($this->defaultButtons());
    }

    private function defaultButtons(): Collection
    {
        return collect([
            view(WRLAHelper::getViewPath('components.forms.button'), [
                'attributes' => new \Illuminate\View\ComponentAttributeBag([
                    'wire:click.prevent' => "flipRead({$this->id})",
                    'title' => $this->read_at === null ? 'Mark as completed' : 'Undo completion',
                ]),
                'text' => $this->read_at === null ? 'Complete' : 'Undo',
                'icon' => $this->read_at === null ? 'fa fa-check' : 'fa fa-undo',
                'size' => 'small',
                'type' => 'button',
                'class' => 'px-4'
            ])
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

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
