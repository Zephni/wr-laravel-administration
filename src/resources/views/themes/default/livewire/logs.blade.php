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
                class="{{ $viewingLogFile == $directoryOrFile ? '!bg-primary-500 !text-white !font-medium' : '' }} w-full grid grid-cols-[32px,1fr] gap-2 items-center px-3 py-2 text-lg odd:bg-slate-100 even:bg-slate-200 dark:bg-slate-700 dark:even:bg-slate-800 rounded-md cursor-pointer hover:bg-white dark:hover:bg-slate-600 text-slate-700 dark:text-white">
                @if(is_array($directoryOrFile))
                    <div class="text-center">
                        <i class="fas fa-folder mr-1.5"></i>
                    </div>
                    <div class="">{{ $key }}</div>
                @else
                    <div class="text-center">
                        <i class="fas fa-file  mr-1.5"></i>
                    </div>
                    <div class="">{{ $directoryOrFile }}</div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="block w-full h-6"></div>

    @if($viewingLogFile !== null)
        <textarea class="w-full" rows="50">{{ $viewingLogContent }}</textarea>
    @endif
</div>