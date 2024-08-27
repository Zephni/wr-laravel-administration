<div>
    <div class="flex justify-between items-center">
        <h3 class="text-lg font-light text-slate-600 mb-3">
            <i class="fas fa-bell text-slate-500 mr-1"></i>
            Notifications / Tasks
        </h3>
        <div class="">
            <div class="relative flex items-center gap-2" style="top: -6px;">
                <div class="text-sm">Show: </div>
                {{-- Status filter --}}
                @themeComponent('forms.input-select', [
                    'attributes' => new \Illuminate\View\ComponentAttributeBag([
                        'wire:model.live' => 'statusFilter',
                        'class' => '!mt-0'
                    ]),
                    'items' => $statusFilterOptions,
                    'options' => []
                ])
            </div>
        </div>
    </div>

    <div class="rounded-lg shadow-lg shadow-slate-300 dark:shadow-slate-850">
        <div class="flex flex-col text-sm text-sm bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-300 rounded-lg overflow-hidden">
            <!-- Header Row -->
            <div class="flex border-b bg-slate-700 dark:bg-slate-400 text-slate-100 dark:text-slate-800 border-slate-400 dark:border-slate-600 py-2">
                <div style="width: 18%;" class="px-2 text-white font-medium">Type</div>
                <div style="width: 59%;" class="px-2 text-white font-medium">Message</div>
                <div style="width: 9%;" class="px-2 text-white font-medium">Date</div>
                <div style="width: 14%;" class="px-2 text-white font-medium"></div>
            </div>
            <!-- Content Rows -->
            @forelse($notifications as $notification)
                @php
                    $definition = $notification->getDefinition();
                @endphp
                <a
                    href="{{ $definition->getLink() }}"
                    target="_blank"
                    title="Follow notification link"
                    class="flex items-center bg-slate-100 dark:bg-slate-700 odd:bg-slate-200 dark:odd:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-600 py-1">
                    <div style="width: 18%; font-size: 13px; line-height: 13px;" class=" px-2 py-2 truncate">{!! $definition->getTitle() !!}</div>
                    <div style="width: 59%; font-size: 13px; line-height: 13px;" class=" px-2 py-2 truncate">{!! $definition->getMessage() !!}</div>
                    <div style="width: 9%; font-size: 13px; line-height: 13px;" class=" px-2 py-2 truncate">{{ $notification->created_at->format('d/m/Y') }}</div>
                    <div style="width: 14%;" class="px-2 py-2 gap-3 truncate flex justify-end items-center">
                        @if($notification->read_at === null)
                            @themeComponent('forms.button', [
                                'attributes' => new \Illuminate\View\ComponentAttributeBag([
                                    'wire:click.prevent' => 'flipRead(' . $notification->id . ')',
                                    'title' => 'Mark as completed',
                                ]),
                                'size' => 'small',
                                'type' => 'button',
                                'text' => 'Complete',
                                'icon' => 'fa fa-check',
                                'class' => 'px-4'
                            ])
                        @else
                            @themeComponent('forms.button', [
                                'attributes' => new \Illuminate\View\ComponentAttributeBag([
                                    'wire:click.prevent' => 'flipRead(' . $notification->id . ')',
                                    'title' => 'Undo completion',
                                ]),
                                'size' => 'small',
                                'type' => 'button',
                                'text' => 'Undo',
                                'icon' => 'fa fa-undo',
                                'class' => 'px-4',
                                'color' => 'grey'
                            ])
                            <i class="fas fa-check-circle text-primary-500 text-lg mr-1"></i>
                        @endif
                    </div>
                </a>
            @empty
                <div class="flex items-center bg-slate-100 dark:bg-slate-700 odd:bg-slate-200 dark:odd:bg-slate-800 py-3">
                    <div class="px-2 py-2 text-slate-500 dark:text-slate-300 text-center text-base w-full">
                        @if($statusFilter === 'unread')
                            <i class="fas fa-check-circle text-primary-500 mr-1"></i>
                            All tasks complete!
                        @elseif($statusFilter === 'read')
                            <i class="fas fa-check-circle text-slate-500 mr-1"></i>
                            No completed notifications found.
                        @else
                            <i class="fas fa-check-circle text-slate-500 mr-1"></i>
                            No notifications found.
                        @endif
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mx-auto p-8 text-center">
        {{ $notifications->links($WRLAHelper::getViewPath('livewire.pagination.tailwind')) }}
    </div>
</div>