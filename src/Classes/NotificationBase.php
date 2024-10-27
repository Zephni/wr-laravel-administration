<?php

namespace WebRegulate\LaravelAdministration\Classes;

use App\Models\User;
use App\WRLA\WRLASettings;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use WebRegulate\LaravelAdministration\Models\Notification;

class NotificationBase
{
    public mixed $userId;
    public array $data;
    public ?User $user;

    public function __construct(mixed $userId, array $data)
    {
        $this->userId = $userId;
        $this->user = is_int($this->userId) ? User::find($userId) : null;
        $this->data = $data;
        $this->mount($data);
    }

    public function getUserGroup(): ?Collection
    {
        return is_int($this->userId) ? collect([$this->user]) : WRLASettings::getUserGroup($this->userId);
    }

    public function optionEqualTo(?array $options, string $key, mixed $value): bool
    {
        return $options !== null && isset($options[$key]) && $options[$key] === $value;
    }

    public function mount(array $data): void
    {
        // Set custom properties here
    }

    public function getTitle(): string
    {
        return 'Notification Example';
    }

    public function getMessage(): string
    {
        return "This is an example of notification, target user: {$this->user->name}, with passed data: {$this->data['example']}";
    }

    public function getEmailMessage(): string
    {
        return $this->getMessage();
    }

    public function getMessageFinal(): string
    {
        $message = $this->getMessage();

        // Remove all all spaces and tabs from the beginning of any lines
        $message = preg_replace('/^[\t ]+/m', '', $message);

        // Get markdown -> html
        $message = Str::markdown($message);

        // Add target _blank to all links
        $message = str_replace('<a href=', '<a target="_blank" href=', $message);

        return $message;
    }

    public function getEmailMessageFinal(): string
    {
        $message = $this->getEmailMessage();

        // Remove all tabs / 4 spaces from begining of lines
        $message = preg_replace('/^[\t ]+/m', '', $message);

        // Return markdown -> html
        return Str::markdown($message);
    }

    public function postCreated(): void
    {
        // Handle email sending, push notifications, etc.
    }

    public function getButtons(Collection $defaultButtons, Notification $notification): Collection
    {
        return $defaultButtons;
    }

    protected function buildNotificationButton(Notification $notification, array $htmlAttributes, string $text, string $icon = 'fas fa-check', string $color = 'primary')
    {
        return NotificationBase::staticBuildNotificationButton($notification, $htmlAttributes, $text, $icon, $color);
    }

    protected function buildNotificationActionButton(Notification $notification, string $localMethod, ?array $methodData, string $text, string $icon = 'fas fa-check', array $additionalHtmlAttributes = [], string $color = 'primary')
    {
        return NotificationBase::staticBuildNotificationButton(
            $notification,
            array_merge([
                'wire:click.prevent' => "callNotificationDefinitionMethod({$notification->id}, '{$localMethod}', '" . json_encode($methodData) . "')",
            ], $additionalHtmlAttributes),
            $text,
            $icon,
            $color
        );
    }

    public static function staticBuildNotificationActionButton(Notification $notification, string $localMethod, ?array $methodData, string $text, string $icon = 'fas fa-check', array $additionalHtmlAttributes = [], string $color = 'primary')
    {
        return NotificationBase::staticBuildNotificationButton(
            $notification,
            array_merge([
                'wire:click.prevent' => "callNotificationDefinitionMethod({$notification->id}, '{$localMethod}', '" . json_encode($methodData) . "')",
            ], $additionalHtmlAttributes),
            $text,
            $icon,
            $color
        );
    }

    public static function staticBuildNotificationButton(Notification $notification, array $htmlAttributes, string $text, string $icon = 'fas fa-check', string $color = 'primary')
    {
        return view(WRLAHelper::getViewPath('components.forms.button'), [
            'attributes' => new \Illuminate\View\ComponentAttributeBag(array_merge([
                'onclick' => "
                    window.buttonSignifyLoading(this, () => new Promise((resolve) => {
                        Livewire.on('notificationWidgetFinishedLoading', () => {
                            resolve();
                        });
                    }));
                ",
            ],$htmlAttributes)),
            'text' => $text,
            'icon' => $icon,
            'color' => $color,
            'size' => 'small',
            'type' => 'button',
            'class' => 'px-4'
        ]);
    }

    public function flipNotificationMarkedAsRead(Notification|int $notification): void
    {
        if (is_int($notification)) {
            $notification = Notification::find($notification);
        }

        $notification->flipMarkedAsRead();
    }

    public function deleteNotification(Notification|int $notification): void
    {
        if (is_int($notification)) {
            $notification = Notification::find($notification);
        }

        $notification->delete();
    }
}
