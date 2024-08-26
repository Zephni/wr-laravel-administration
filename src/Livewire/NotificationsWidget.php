<?php

namespace WebRegulate\LaravelAdministration\Livewire;

use Livewire\Component;
use Illuminate\Contracts\View\View;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Models\Notification;

class NotificationsWidget extends Component
{
    public function mount()
    {
        
    }

    public function render(): View
    {
        $notifications = Notification::where('user_id', 'admin')
            ->where('read_at', null)
            ->orderBy('created_at', 'desc')
            ->paginate(15);
            
        return view(WRLAHelper::getViewPath('livewire.notifications-widget'), [
            'notifications' => $notifications
        ]);
    }

    public function markAsRead(int $id)
    {
        $notification = Notification::find($id);
        $notification->read_at = now();
        $notification->save();

        // Refresh
        $this->render();
    }
}