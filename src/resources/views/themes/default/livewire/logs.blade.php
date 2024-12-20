<div>
    <div class="pb-6">
        <h1 class="text-2xl font-light">
            <i class="fas fa-file text-slate-700 dark:text-white mr-1"></i>
            Viewing Logs {{ empty($viewingLogsDirectory) ? '' : 'in /'.str_replace('.', '/', $viewingLogsDirectory) }}
        </h1>
        <hr class="border-b border-slate-400 w-80 mt-1 mb-3">
    </div>
    
    <div class="w-full flex flex-col">
        @foreach($currentDirectoriesAndFiles as $key => $directoryOrFile)
            <div
                @if(is_dir(storage_path('logs/'.$key)))
                    wire:click="switchDirectory('{{ $key }}')"
                @else
                    wire:click="viewLogFile('{{ $viewingLogsDirectory }}', '{{ $directoryOrFile }}')"
                @endif
                class="{{ $viewingLogFile == $directoryOrFile ? '!bg-primary-500 !text-white !font-bold' : '' }} w-full grid grid-cols-[32px,1fr] gap-2 items-center px-3 py-2 text-lg bg-gray-100 dark:bg-slate-700 rounded-md cursor-pointer hover:bg-white dark:hover:bg-slate-600 text-slate-700 dark:text-white">
                @if(is_array($directoryOrFile))
                    <div class="text-center">
                        <i class="{{ $viewingLogFile == $directoryOrFile ? '!text-white' : '' }} fas fa-folder text-amber-400 mr-1.5"></i>
                    </div>
                    <div class="">{{ $key }}</div>
                @else
                    <div class="text-center">
                        <i class="{{ $viewingLogFile == $directoryOrFile ? '!text-white' : '' }} fas fa-file text-primary-500 mr-1.5"></i>
                    </div>
                    <div class="">{{ $directoryOrFile }}</div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="block w-full h-6"></div>

    <div class="flex justify-end items-center gap-3">
        {{-- Loading spinner --}}
        <div wire:loading.flex class="justify-end items-center gap-2 text-base" style="line-height: 0px;">
            <i class="fas fa-spinner fa-spin"></i>
            <span>Loading...</span>
        </div>

        {{-- Refresh button --}}
        @themeComponent('forms.button', [
            'type' => 'button',
            'size' => 'small',
            'text' => 'Refresh',
            'icon' => 'fas fa-sync-alt',
            'wire:click' => 'render',
        ])
    </div>

    @if($viewingLogFile !== null)
        {{-- <textarea class="w-full" rows="50">{{ $viewingLogContent }}</textarea> --}}
        {{-- Textarea --}}
        {!! view($WRLAHelper::getViewPath('components.forms.textarea'), [
            'label' => 'Log Content',
            'options' => [],
            'attributes' => new \Illuminate\View\ComponentAttributeBag([
                'wire:model' => 'viewingLogContent',
                'name' => 'log_content',
                'class' => '!bg-slate-100 dark:!bg-slate-800 h-64',
            ]),
        ])->render() !!}
    @endif
</div>