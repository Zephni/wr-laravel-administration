<div>
    @themeComponent('forms.button', [
        'href' => route('wrla.manageable-models.browse', ['modelUrlAlias' => $manageableModelClass::getUrlAlias()]),
        'text' => $manageableModelClass::getDisplayName(true),
        'size' => 'small',
        'color' => 'primary',
        'icon' => 'fa fa-arrow-left',
    ])

    <br />

    {{-- Generic error message --}}
    @error('error')
        @themeComponent('alert', ['type' => 'error', 'message' => $message])
    @enderror

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
            @foreach($manageableModel->getInstanceActionsFinal() as $key => $instanceAction)
                @continue($key == 'edit')
                {!! $instanceAction?->render() ?? '' !!}
            @endforeach
        </div>
    </div>

    {{-- Form --}}
    <form
        id="upsert-form"
        action="{{ route('wrla.manageable-models.upsert.post', [
            'modelUrlAlias' => $manageableModel->getUrlAlias(),
            'modelId' => $manageableModel->getmodelInstance()->id,
        ]) }}"
        enctype="multipart/form-data"
        method="POST"
        class="w-full">
        @csrf

        <div class="flex flex-wrap gap-6 mt-4 p-4 bg-slate-100 dark:bg-slate-800 dark:border-slate-700 border shadow-slate-300 dark:shadow-slate-850 rounded-lg shadow-lg">
            @if(!empty($manageableFields))
                @foreach($manageableFields as $manageableField)
                    {!! $manageableField->renderParent($upsertType, $livewireData) !!}
                @endforeach
            @else
                <div class="text-slate-600 my-3" style="line-height: 2rem;">
                    <b class="font-medium text-primary-600">
                        <i class="fa fa-info-circle mr-0.5"></i>
                        No Manageable Fields found
                    </b><br />
                    Add Manageable Fields for this model in the <b class="font-medium text-primary-600">{{ $manageableModel::class }} -> getManageableFields()</b> method
                </div>
            @endif
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
                'color' => 'secondary',
                'icon' => 'fa fa-times',
            ])
        </div>

    </form>

    @if($user->wrlaUserData?->getSetting('debug') == true)
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

    {{-- Gap --}}
    <div class="block h-24"></div>
</div>

@if($usesWysiwyg === true)
    @push('append-body')
        {!! $WRLAHelper::getWysiwygEditorSetupJS() !!}
    @endpush
@endif
