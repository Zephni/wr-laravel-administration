<?php

namespace WebRegulate\LaravelAdministration\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Models\Notification;

class NotificationsWidget extends Component
{
    use WithPagination;

    public string $statusFilter = 'unread';

    public array $statusFilterOptions = [
        'unread' => 'Outstanding',
        'read' => 'Completed',
        'all' => 'All',
    ];

    public array $userIds = []; // Set in mount

    public function updatedStatusFilter()
    {
        $this->render();
    }

    public function mount(array $userIds = ['admin'])
    {
        $this->userIds = $userIds;
    }

    public function render(): View
    {
        $notifications = Notification::baseBuilderForUserIds($this->userIds)
            ->when($this->statusFilter === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->when($this->statusFilter === 'read', fn ($query) => $query->whereNotNull('read_at'))
            ->paginate(15);

        return view(WRLAHelper::getViewPath('livewire.notifications-widget'), [
            'notifications' => $notifications,
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

    public function callNotificationDefinitionMethod(int $notificationId, string $methodName, ?string $methodData = null)
    {
        // Decode method data
        if ($methodData !== null) {
            $methodData = json_decode($methodData, true);
        }

        // Get notification
        $notification = Notification::find($notificationId);

        // Get definition
        $notificationDefinition = $notification->getDefinition();

        // If method data null, call method without data
        if ($methodData === null) {
            $notificationDefinition->$methodName();
            // Otherwise, call method with data array
        } else {
            $notificationDefinition->$methodName(...$methodData);
        }

        // Refresh
        $this->render();

        // Emit event
        $this->dispatch('notificationWidgetFinishedLoading');
    }
}
