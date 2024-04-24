<div>
    @if($manageableModel->modelInstance->id == null)
        Creating new {{ $manageableModel->getDisplayName() }}
    @else
        Editing {{ $manageableModel->getDisplayName() }}, ID: {{ $manageableModel->modelInstance->id }}
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
        ])

        @themeComponent('forms.button', [
            'href' => url()->previous(),
            'text' => 'Cancel',
            'size' => 'medium',
            'color' => 'danger',
            'icon' => 'fa fa-times',
        ])
    </div>
</div>
