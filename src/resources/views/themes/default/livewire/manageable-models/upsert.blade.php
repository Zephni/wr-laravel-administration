<div>
    @themeComponent('forms.button', [
        'href' => route('wrla.manageable-models.browse', ['modelUrlAlias' => $manageableModelClass::getUrlAlias()]),
        'text' => $manageableModelClass::getDisplayName(true),
        'size' => 'small',
        'color' => 'primary',
        'icon' => 'fa fa-arrow-left',
    ])

    <br />

    {{-- Heading --}}
    <div class="flex justify-between">
        <div class="text-xl font-semibold">
            @if(!empty($overrideTitle))
                {{ $overrideTitle }}
            @else
                @if($manageableModel->getmodelInstance()->id == null)
                    Creating new {{ $manageableModel->getDisplayName() }}
                @else
                    Editing {{ $manageableModel->getDisplayName() }} #{{ $manageableModel->getmodelInstance()->id }}
                @endif
            @endif
        </div>
        <div class="flex justify-end gap-2 !text-sm">
            @foreach($manageableModel->getInstanceActionsFinal() as $key => $action)
                @continue($key == 'edit')
                {!! $action->render() !!}
            @endforeach
        </div>
    </div>

    {{-- Form --}}
    <form
        action="{{ route('wrla.manageable-models.upsert.post', [
            'modelUrlAlias' => $manageableModel->getUrlAlias(),
            'modelId' => $manageableModel->getmodelInstance()->id,
        ]) }}"
        enctype="multipart/form-data"
        method="POST"
        class="w-full">
        @csrf
        
        <div class="flex flex-wrap gap-6 mt-4 p-4 bg-slate-100 dark:bg-slate-700 shadow-slate-300 dark:shadow-slate-850 rounded-lg shadow-lg">
            @foreach($manageableFields as $manageableField)
                {!! $manageableField->renderParent($upsertType, $livewireData) !!}
            @endforeach
        </div>

        <div class="flex justify-center gap-4 mt-10">
            @themeComponent('forms.button', [
                'type' => 'submit',
                'size' => 'medium',
                'color' => 'primary',
                'text' => 'Save',
                'icon' => 'fa fa-edit',
            ])

            @themeComponent('forms.button', [
                'href' => route('wrla.manageable-models.browse', ['modelUrlAlias' => $manageableModel->getUrlAlias()]),
                'text' => 'Cancel',
                'size' => 'medium',
                'color' => 'muted',
                'icon' => 'fa fa-times',
            ])
        </div>

    </form>
    
    @if($WRLAUser->getSetting('debug') == true)
        <div class="border border-slate-300 rounded-md p-2 mt-10 text-slate-500">
            <p class=" text-sm font-semibold">Debug Information:</p>
            <hr class="my-1 border-slate-300">
            Render counter: {{ $numberOfRenders }}<br />
            Livewire data ({{ count($livewireData) }}):<br />
            @foreach($livewireData as $key => $value)
                {{ $key }}: <b class="font-medium">{{ $value }}</b><br />
            @endforeach
        </div>
    @endif
</div>


@if($usesWysiwyg === true)
    @push('appendBody')
        <script src="https://cdn.tiny.cloud/1/126uh4v0nur2ag6fpa5vb60rduwp1skzx02vsmdww39mpva2/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
        <script>
            tinymce.init({
                selector: '.wrla_wysiwyg',
                plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount code paste fullscreen',
                menubar: 'edit view insert tools table',
                toolbar: 'undo redo | bold italic underline | link media table | align | numlist bullist indent | code',
                paste_data_images: true,
                relative_urls : false,
                content_style: `{{ config('wr-laravel-administration.wysiwyg_css') }}`,
            });
        </script>
    @endpush
@endif