<?php

namespace WebRegulate\LaravelAdministration\Livewire;

use Livewire\Component;
use Illuminate\Contracts\View\View;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Models\Notification;

class NotificationsWidget extends Component
{
    public string $statusFilter = 'unread';
    public array $statusFilterOptions = [
        'unread' => 'Outstanding',
        'read' => 'Completed',
        'all' => 'All',
    ];

    public function updatedStatusFilter()
    {
        $this->render();
    }

    public function mount()
    {
        
    }

    public function render(): View
    {
        $notifications = Notification::where('user_id', 'admin')
            ->when($this->statusFilter === 'unread', function ($query) {
                return $query->whereNull('read_at');
            })
            ->when($this->statusFilter === 'read', function ($query) {
                return $query->whereNotNull('read_at');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);
            
        return view(WRLAHelper::getViewPath('livewire.notifications-widget'), [
            'notifications' => $notifications
        ]);
    }

    public function flipRead(int $id)
    {
        $notification = Notification::find($id);
        $notification->read_at = ($notification->read_at == null) ? now() : null;
        $notification->save();

        // Refresh
        $this->render();
    }
}