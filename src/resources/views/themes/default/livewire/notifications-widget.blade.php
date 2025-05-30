<div>
    <div class="flex justify-between items-center">
        <h3 class="text-lg font-light mb-3">
            <i class="fas fa-bell mr-1"></i>
            Notifications / Tasks
        </h3>
        <div class="">
            <div class="relative flex items-center gap-5" style="top: -9px;">
                <div class="flex items-center gap-2">
                    <div class="text-sm">Show: </div>
                    {{-- Status filter --}}
                    @themeComponent('forms.input-select', [
                        'attributes' => Arr::toAttributeBag([
                            'wire:model.live' => 'statusFilter',
                            'class' => '!bg-slate-100 dark:!bg-slate-800 !mt-0'
                        ]),
                        'items' => $statusFilterOptions,
                        'options' => []
                    ])
                </div>
            </div>
        </div>
    </div>

    <div class="w-full max-w-full rounded-lg shadow-lg shadow-slate-300 dark:shadow-slate-850">
        <div class="flex flex-col text-sm bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-300 rounded-lg overflow-hidden">
            <!-- Header Row -->
            <table class="w-full table-auto text-left align-middle">
                <thead>
                    <tr class="border-b bg-slate-700 dark:bg-slate-700 text-slate-100 dark:text-slate-300 border-slate-400 dark:border-slate-600 py-2">
                        <th class="px-2 py-2 font-medium">Type</th>
                        <th class="px-2 py-2 font-medium">Message</th>
                        <th class="px-2 py-2 font-medium">Created</th>
                        <th class="px-2 py-2 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Content Rows -->
                    @forelse($notifications as $notification)
                        @php
                            $definition = $notification->getDefinition();
                        @endphp
                        <tr target="_blank" class="bg-slate-100 dark:bg-slate-800 odd:bg-slate-200 dark:odd:bg-slate-900 py-1">
                            <td style="font-size: 13px;" class="px-2 py-2 truncate font-medium [&_a]:underline">{!! $definition->getTitle() !!}</td>
                            {{-- String replace <a href=" with <a target="_blank" href=" --}}
                            <td style="font-size: 13px;" class="text-wrap px-2 py-2 truncate [&_a]:font-medium [&_a]:underline">{!! $definition->getMessageFinal(true) !!}</div>
                            <td style="font-size: 13px;" class="px-2 py-2 truncate">{{ $notification->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-2 py-2">
                                <div class="flex justify-end items-center gap-2 truncate">
                                    {{-- Notification buttons --}}
                                    @foreach($notification->getFinalButtons() as $button)
                                        {!! $button !!}
                                    @endforeach
        
                                    @if($notification->read_at !== null)
                                        <i class="fas fa-check-circle text-primary-500 text-lg mr-1" title="Read / Completed"></i>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="bg-slate-100 dark:bg-slate-800 py-3">
                            <td colspan="4" class="px-2 py-6 text-center text-base">
                                @if($statusFilter === 'unread')
                                    <i class="fas fa-check-circle text-primary-500 mr-1"></i>
                                    All tasks complete!
                                @elseif($statusFilter === 'read')
                                    <i class="fas fa-check-circle mr-1"></i>
                                    No completed notifications found.
                                @else
                                    <i class="fas fa-check-circle mr-1"></i>
                                    No notifications found.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mx-auto p-8 text-center">
        {{ $notifications->links($WRLAHelper::getViewPath('livewire.pagination.tailwind')) }}
    </div>
</div>