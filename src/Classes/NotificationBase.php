<?php

namespace WebRegulate\LaravelAdministration\Classes;

use App\Models\User;
use App\WRLA\WRLASettings;
use Illuminate\Support\Collection;

class NotificationBase
{
    public mixed $userId;
    public array $data;
    public string $title;
    public string $message;
    public string $link;
    public ?User $user;

    public function __construct(mixed $userId, array $data)
    {
        $this->userId = $userId;
        $this->user = is_int($this->userId) ? User::find($userId) : null;
        $this->data = $data;
        $this->title = $this->getTitle();
        $this->message = $this->getMessage();
        $this->link = $this->getLink();
    }

    public function getUserGroup(): ?Collection
    {
        return is_int($this->userId) ? collect([$this->user]) : WRLASettings::getUserGroup($this->userId);
    }

    public function getTitle(): string
    {
        return 'Notification Example';
    }

    public function getMessage(): string
    {
        return "This is an example of notification, target user: {$this->user->name}, with passed data: {$this->data['example']}";
    }

    public function getLink(): string
    {
        return '/';
    }

    public function postCreated(): void
    {
        // Handle email sending, push notifications, etc.
    }
}
