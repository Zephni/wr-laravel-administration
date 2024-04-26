<div>
    @themeComponent('forms.button', [
        'href' => route('wrla.manageable-model.browse', ['modelUrlAlias' => $manageableModel->getUrlAlias()]),
        'text' => 'Back',
        'size' => 'small',
        'color' => 'primary',
        'icon' => 'fa fa-arrow-left',
    ])

    <br />

    @if($manageableModel->getmodelInstance()->id == null)
        Creating new {{ $manageableModel->getDisplayName() }}
    @else
        Editing {{ $manageableModel->getDisplayName() }}, ID: {{ $manageableModel->getmodelInstance()->id }}
    @endif

    <div class="flex flex-col gap-4 mt-12 p-4 bg-slate-100 dark:bg-slate-700 shadow-slate-300 dark:shadow-slate-850 rounded-lg shadow-lg">
        @foreach($manageableModel->getManageableFields() as $manageableField)
            {!! $manageableField->render() !!}
        @endforeach
    </div>

    <div class="flex justify-center gap-4 mt-10">
        @themeComponent('forms.button', [
            'type' => 'submit',
            'size' => 'medium',
            'color' => 'primary',
            'text' => 'Save',
            'icon' => 'fa fa-edit',
            'attr' => [
                'wire:click' => 'save',
            ]
        ])

        @themeComponent('forms.button', [
            'href' => route('wrla.manageable-model.browse', ['modelUrlAlias' => $manageableModel->getUrlAlias()]),
            'text' => 'Cancel',
            'size' => 'medium',
            'color' => 'danger',
            'icon' => 'fa fa-times',
        ])
    </div>
</div>
