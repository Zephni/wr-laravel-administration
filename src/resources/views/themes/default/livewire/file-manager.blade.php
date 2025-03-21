<div>
    <div class="relative pb-6">
        <h1 class="text-2xl font-light">
            <i class="fa fa-folder-open text-slate-700 dark:text-white mr-1"></i>
            File Manager 
        </h1>
        <hr class="border-b border-slate-400 w-80 mt-1 mb-3">

        {{-- Refresh button --}}
        @themeComponent('forms.button', [
            'type' => 'button',
            'size' => 'small',
            'text' => 'Refresh',
            'icon' => 'fas fa-sync-alt',
            'attributes' => new \Illuminate\View\ComponentAttributeBag([
                'class' => 'absolute top-0 right-0 mt-2',
                'wire:click' => 'refresh',
            ])
        ])
    </div>

    <div class="flex justify-between items-center gap-3 mb-4">
        <div class="flex-1">
            <p class="w-full text-lg px-2 font-normal text-slate-600 border-b border-slate-400">
                <span class="text-sky-600 font-medium cursor-pointer hover:underline" wire:click="switchDirectory('')">{{ $currentFileSystemName }}</span>
    
                @php $filePathParts = []; @endphp
                @foreach(explode('/', ltrim($fullFilePath, '/')) as $filePathPart)
                    @php $filePathParts[] = $filePathPart; @endphp
                    /
                    @if(!$loop->last)
                        <span type="button" wire:click="switchDirectory('{{ implode('.', $filePathParts) }}')" class="cursor-pointer hover:underline">{{ $filePathPart }}</span>
                    @else
                        <span class="text-slate-700">{{ $filePathPart }}</span>
                    @endif
                @endforeach
            </p>
        </div>
        <div class="flex items-center gap-2 whitespace-nowrap">
            {{-- Loading spinner --}}
            <div wire:loading.flex class="justify-end items-center gap-2 text-base" style="line-height: 0px;">
                <i class="fas fa-spinner fa-spin"></i>
                <span>Loading...</span>
            </div>

            <label
                for="fileUpload"
                x-on:click="$wire.set('uploadFilePath', '{{ trim(str_replace('.', '/', $viewingDirectory), '/') }}')"
                class="flex justify-center items-center gap-1 w-fit px-2 text-[14px] !h-[22.6px] font-semibold border bg-primary-600 dark:bg-primary-800 text-white dark:text-slate-200 hover:brightness-110 border-teal-500 dark:border-teal-600 shadow-slate-400 dark:shadow-slate-700 rounded-md shadow-sm whitespace-nowrap cursor-pointer"
            >
                <i class="fas fa-upload text-xs mr-1"></i>
                Upload file
            </label>
            <input
                id="fileUpload"
                type="file"
                wire:model.live="uploadFile"
                accept="*/*"
                class="hidden" />
        </div>
    </div>
    
    <div class="relative flex items-start gap-4">
        {{-- Folders / Files list --}}
        <div class="w-full flex flex-col" style="@if($viewingItemType !== null && $highlightedItem !== null) width: 62%; @endif">
            @foreach($currentDirectoriesAndFiles as $key => $directoryOrFile)
                <div
                    class="{{ $highlightedItem !== null && $highlightedItem == $directoryOrFile ? '!bg-slate-50 !border-primary-500 !border-2 !font-bold' : '' }} w-full flex justify-between items-center gap-2 px-3 h-12 text-lg bg-gray-100 dark:bg-slate-700 first:rounded-t-md last:rounded-b-md hover:bg-white dark:hover:bg-slate-600 text-slate-700 dark:text-white even:bg-opacity-60 whitespace-nowrap truncate">
                    
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
                            <div>{{ $directoryOrFile }}</div>
                        @endif
                    </div>
    
                    <div class="flex justify-end items-center gap-2">
                        {{-- If is file --}}
                        @if($directoryOrFile != '..' && !is_array($directoryOrFile))
                            <label
                                for="fileReplace"
                                x-on:click="$wire.set('replaceFilePath', '{{ trim(trim(str_replace('.', '/', $viewingDirectory), '/').'/'.$directoryOrFile, '/') }}')"
                                class="flex justify-center items-center gap-1 w-fit px-2 text-[14px] !h-[22.6px] font-semibold border bg-primary-600 dark:bg-primary-800 text-white dark:text-slate-200 hover:brightness-110 border-teal-500 dark:border-teal-600 shadow-slate-400 dark:shadow-slate-700 rounded-md shadow-sm whitespace-nowrap cursor-pointer"
                            >
                                <i class="fas fa-upload text-xs mr-1"></i>
                                Replace
                            </label>
                            <input
                                id="fileReplace"
                                type="file"
                                wire:model.live="replaceFile"
                                accept="*/*"
                                class="hidden" />
                        @endif

                        {{-- If is valid directory or file --}}
                        @if($directoryOrFile != '..')
                            {{-- Delete button --}}
                            @themeComponent('forms.button', [
                                'type' => 'button',
                                'size' => 'small',
                                'color' => 'danger',
                                'text' => 'Delete',
                                'icon' => 'fas fa-trash text-xs leading-0',
                                'attributes' => new \Illuminate\View\ComponentAttributeBag([
                                    'title' => 'Delete file',
                                    'class' => '!py-0 !leading-0 !h-[22.6px]',
                                    'wire:click' => "deleteFile('$viewingDirectory', '".(is_array($directoryOrFile) ? $key : $directoryOrFile)."')",
                                ])
                            ])
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- If file system set --}}
        @if($currentFileSystemName !== null)
    
            @if($viewingItemType !== null && $highlightedItem !== null)
                <div class="sticky top-12" style="width: 38%;">
                    {{-- Preview text --}}
                    <p class="mb-3 px-2 text-sm text-slate-500 border-b border-slate-400">
                        <i class="fas fa-eye mr-1 text-slate-400 text-xs"></i>
                        Preview {{ $viewingItemType }}
                    </p>

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
                            <img src="{{ $viewingItemContent }}" alt="{{ $highlightedItem }}" class="w-full max-w-full">
                        </div>
                    {{-- Video --}}
                    @elseif($viewingItemType == 'video')
                        <div class="w-full flex justify-center items-center">
                            <video controls class="max-w-full max-h-64">
                                <source src="{{ $viewingItemContent }}" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        </div>
                    @else
                        <div class="w-full flex justify-center items-center mt-3">
                            <i class="fas fa-info-circle text-slate-400 mr-1"></i>
                            {{ $viewingItemType }} preview not available.
                        </div>
                    @endif

                </div>
            @endif

        @endif
    </div>

    <div class="block w-full h-6"></div>

    {{-- If no file selected / found --}}
    @if($viewingItemType === null || $highlightedItem === null)

        <div class="w-full text-center text-lg text-slate-700 dark:text-white">
            <i class="fas fa-info-circle text-slate-400 mr-1"></i>
            No file selected / found
        </div>

    {{-- If file system not set --}}
    @elseif($currentFileSystemName === null)
        
        <div class="w-full text-center text-lg text-slate-700 dark:text-white">
            <i class="fas fa-info-circle text-slate-400 mr-1"></i>
            No file system found or selected.<br />
            <br />
            Set within wr-laravel-administration.file_manager config.
        </div>

    @endif

    {{-- @dump($debug) --}}
</div>