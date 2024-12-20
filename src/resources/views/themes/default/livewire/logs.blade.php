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
                class="{{ $viewingLogFile !== null && $viewingLogFile == $directoryOrFile ? '!bg-slate-50 !border-primary-500 !border-2 !font-bold' : '' }} w-full flex justify-between items-center gap-2 px-3 h-12 text-lg bg-gray-100 dark:bg-slate-700 first:rounded-t-md last:rounded-b-md hover:bg-white dark:hover:bg-slate-600 text-slate-700 dark:text-white even:bg-opacity-60">
                
                <div
                    @if($directoryOrFile == '..')
                        wire:click="switchDirectory('..')"
                    @elseif(is_array($directoryOrFile))
                        wire:click="switchDirectory('{{ (empty($viewingLogsDirectory) ? '' : $viewingLogsDirectory.'.').$key }}')"
                        title="{{ ltrim((empty($viewingLogsDirectory) ? '' : str_replace('.', '/', $viewingLogsDirectory).'/').$key, '/') }}"
                    @else
                        wire:click="viewLogFile('{{ $viewingLogsDirectory }}', '{{ $directoryOrFile }}')"
                        title="{{ ltrim(str_replace('.', '/', $viewingLogsDirectory).'/'.$directoryOrFile, '/') }}"
                    @endif
                    class="w-full h-full flex items-center gap-2 cursor-pointer">
                    @if($directoryOrFile == '..' || is_array($directoryOrFile))
                        <div class="text-center">
                            <i class="fas fa-folder text-amber-400 mr-1.5"></i>
                        </div>
                        <div class="">{{ $key }} {{ is_array($directoryOrFile) ? '('.count($directoryOrFile).')' : '' }}</div>
                    @else
                        <div class="text-center">
                            <i class="fas fa-file text-primary-500 mr-1.5"></i>
                        </div>
                        <div class="">{{ $directoryOrFile }}</div>
                    @endif
                </div>

                <div class="flex justify-end items-center gap-2">
                    @if($directoryOrFile != '..')
                        {{-- Delete button --}}
                        @themeComponent('forms.button', [
                            'type' => 'button',
                            'size' => 'small',
                            'color' => 'danger',
                            'text' => 'Delete',
                            'icon' => 'fas fa-trash',
                            'attributes' => new \Illuminate\View\ComponentAttributeBag([
                                'wire:click' => "deleteLogFile('$viewingLogsDirectory', '".(is_array($directoryOrFile) ? $key : $directoryOrFile)."')",
                            ])
                        ])
                    @endif
                </div>
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
            'attributes' => new \Illuminate\View\ComponentAttributeBag([
                'wire:click' => '$refresh',
            ])
        ])
    </div>

    @if($viewingLogFile !== null)
        {{-- <textarea class="w-full" rows="50">{{ $viewingLogContent }}</textarea> --}}
        {{-- Textarea --}}
        {!! view($WRLAHelper::getViewPath('components.forms.textarea'), [
            'label' => $viewingLogFile,
            'options' => [],
            'attributes' => new \Illuminate\View\ComponentAttributeBag([
                'wire:model' => 'viewingLogContent',
                'name' => 'log_content',
                'class' => '!bg-slate-100 dark:!bg-slate-800 h-64',
            ]),
        ])->render() !!}
    @else
        <div class="w-full text-center text-lg text-slate-700 dark:text-white">
            No logs found in: logs/{{ str_replace('.', '/', $viewingLogsDirectory) }}
        </div>
    @endif
</div>