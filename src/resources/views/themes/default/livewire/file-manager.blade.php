<div>
    <div class="pb-6">
        <h1 class="text-2xl font-light">
            <i class="fa fa-folder-open text-slate-700 dark:text-white mr-1"></i>
            File Manager 
            <span class="text-slate-700">
                <span class="text-sky-600 font-medium cursor-pointer hover:underline" wire:click="switchDirectory('')">{{ $currentFileSystemName }}</span>

                @php $directoryParts = []; @endphp
                @foreach(explode('/', $fullDirectoryPath) as $directoryPart)
                    @php $directoryParts[] = $directoryPart; @endphp
                    / <span type="button" wire:click="switchDirectory('{{ implode('.', $directoryParts) }}')" class="cursor-pointer hover:underline">{{ $directoryPart }}</span>
                @endforeach
            </span>
        </h1>
        <hr class="border-b border-slate-400 w-80 mt-1 mb-3">
    </div>

    <div class="flex justify-end items-center gap-3 mb-4">
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
                'wire:click' => 'refresh',
            ])
        ])
    </div>
    
    <div class="w-full flex flex-col">
        @foreach($currentDirectoriesAndFiles as $key => $directoryOrFile)
            <div
                class="{{ $highlightedItem !== null && $highlightedItem == $directoryOrFile ? '!bg-slate-50 !border-primary-500 !border-2 !font-bold' : '' }} w-full flex justify-between items-center gap-2 px-3 h-12 text-lg bg-gray-100 dark:bg-slate-700 first:rounded-t-md last:rounded-b-md hover:bg-white dark:hover:bg-slate-600 text-slate-700 dark:text-white even:bg-opacity-60">
                
                <div
                    @if($directoryOrFile == '..')
                        wire:click="switchDirectory('..')"
                    @elseif(is_array($directoryOrFile))
                        wire:click="switchDirectory('{{ (empty($viewingDirectory) ? '' : $viewingDirectory.'.').$key }}')"
                        title="{{ ltrim((empty($viewingDirectory) ? '' : str_replace('.', '/', $viewingDirectory).'/').$key, '/') }}"
                    @else
                        wire:click="viewFile('{{ $viewingDirectory }}', '{{ $directoryOrFile }}')"
                        title="{{ ltrim(str_replace('.', '/', $viewingDirectory).'/'.$directoryOrFile, '/') }}"
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
                                'wire:click' => "deleteFile('$viewingDirectory', '".(is_array($directoryOrFile) ? $key : $directoryOrFile)."')",
                            ])
                        ])
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="block w-full h-6"></div>

    {{-- If file system set --}}
    @if($currentFileSystemName !== null)

        <p class="text-lg mb-3 px-2 font-normal text-slate-600 border-b border-slate-400">
            <span class="text-sky-600 font-medium cursor-pointer hover:underline" wire:click="switchDirectory('')">{{ $currentFileSystemName }}</span>

            @php $filePathParts = []; @endphp
            @foreach(explode('/', $fullFilePath) as $filePathPart)
                @php $filePathParts[] = $filePathPart; @endphp
                /
                @if(!$loop->last)
                    <span type="button" wire:click="switchDirectory('{{ implode('.', $filePathParts) }}')" class="cursor-pointer hover:underline">{{ $filePathPart }}</span>
                @else
                    <span class="text-slate-700">{{ $filePathPart }}</span>
                @endif
            @endforeach
        </p>

        @if($viewingItemType !== null && $highlightedItem !== null)
            {{-- Text --}}
            @if($viewingItemType == 'text' || $viewingItemType == 'error')
                {!! view($WRLAHelper::getViewPath('components.forms.textarea'), [
                    'label' => null,
                    'options' => [],
                    'attributes' => new \Illuminate\View\ComponentAttributeBag([
                        'readonly' => true,
                        'wire:model' => 'viewingItemContent',
                        'name' => 'log_content',
                        'class' => '!bg-slate-100 dark:!bg-slate-800 h-64',
                    ]),
                ])->render() !!}
            {{-- Image --}}
            @elseif($viewingItemType == 'image')
                <div class="w-full flex justify-center items-center">
                    <img src="{{ $viewingItemContent }}" alt="{{ $highlightedItem }}" class="max-w-full max-h-64">
                </div>
            {{-- Video --}}
            @elseif($viewingItemType == 'video')
                <div class="w-full flex justify-center items-center">
                    <video controls class="max-w-full max-h-64">
                        <source src="{{ $viewingItemContent }}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>
            @endif
        @else
            <div class="w-full text-center text-lg text-slate-700 dark:text-white">
                <i class="fas fa-info-circle text-slate-400 mr-1"></i>
                No files found.
            </div>
        @endif

    {{-- If file system not set --}}
    @else

        <div class="w-full text-center text-lg text-slate-700 dark:text-white">
            <i class="fas fa-info-circle text-slate-400 mr-1"></i>
            No file system found or selected.<br />
            <br />
            Set within wr-laravel-administration.file_manager config.
        </div>

    @endif

    {{-- <br /><br />
    @dump($debug) --}}
</div>