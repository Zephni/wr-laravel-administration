<?php

namespace WebRegulate\LaravelAdministration\Classes;

use App\Models\User;

class NotificationBase
{
    public User $viewingUser;
    public array $data;
    public string $title;
    public string $message;
    public string $link;

    public function __construct(User $viewingUser, array $data)
    {
        $this->viewingUser = $viewingUser;
        $this->data = $data;
        $this->title = $this->getTitle();
        $this->message = $this->getMessage();
        $this->link = $this->getLink();
    }

    public function getTitle(): string
    {
        return 'Notification Example';
    }

    public function getMessage(): string
    {
        return "This is an example of notification, viewed by user: {$this->viewingUser->name}, with passed data: {$this->data['example']}";
    }

    public function getLink(): string
    {
        return '/';
    }
}