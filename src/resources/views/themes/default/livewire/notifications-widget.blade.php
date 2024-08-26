<div class="rounded-lg shadow-lg shadow-slate-300 dark:shadow-slate-850">
    <div class="flex flex-col text-sm text-sm bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-300 rounded-lg overflow-hidden">
        <!-- Header Row -->
        <div class="flex border-b bg-slate-700 dark:bg-slate-400 text-slate-100 dark:text-slate-800 border-slate-400 dark:border-slate-600 py-2">
            <div style="width: 18%;" class="px-2 text-white font-medium">Type</div>
            <div style="width: 57%;" class="px-2 text-white font-medium">Message</div>
            <div style="width: 10%;" class="px-2 text-white font-medium">Date</div>
            <div style="width: 15%;" class="px-2 text-white font-medium"></div>
        </div>
        <!-- Content Rows -->
        @foreach($notifications as $notification)
            @php
                $definition = $notification->getDefinition();
            @endphp
            <a
                href="{{ $definition->link }}"
                target="_blank"
                title="Follow notification link"
                class="flex bg-slate-100 dark:bg-slate-700 odd:bg-slate-200 dark:odd:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-600">
                <div style="width: 18%;" class=" px-2 py-2 truncate">{{ $definition->title }}</div>
                <div style="width: 57%;" class=" px-2 py-2 truncate">{{ $definition->message }}</div>
                <div style="width: 10%;" class=" px-2 py-2 truncate">{{ $notification->created_at->format('d/m/Y') }}</div>
                <div style="width: 15%;" class="px-2 py-2 truncate flex justify-end items-center">
                    @themeComponent('forms.button', [
                        'attributes' => new \Illuminate\View\ComponentAttributeBag([
                            'wire:click' => 'markAsRead('.$notification->id.')'
                        ]),
                        'size' => 'small',
                        'type' => 'button',
                        'text' => 'Completed',
                        'icon' => 'fa fa-check',
                        'class' => 'px-4 !py-0'
                    ])
                </div>
            </a>
        @endforeach
    </div>
</div>