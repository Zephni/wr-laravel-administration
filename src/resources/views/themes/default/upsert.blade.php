@extends($WRLAHelper::getViewPath("layouts.admin-layout"))

@section('title', $upsertType->getString() . ' ' . $manageableModel->getDisplayName())

@section('content')

    @themeComponent('forms.button', [
        'href' => route('wrla.manageable-models.browse', ['modelUrlAlias' => $manageableModel->getUrlAlias()]),
        'text' => $manageableModel->getDisplayName(true),
        'size' => 'small',
        'color' => 'primary',
        'icon' => 'fa fa-arrow-left',
    ])

    <br />

    <div class="flex justify-between">
        <div class="text-xl font-semibold">
            @if($manageableModel->getmodelInstance()->id == null)
                Creating new {{ $manageableModel->getDisplayName() }}
            @else
                Editing {{ $manageableModel->getDisplayName() }} #{{ $manageableModel->getmodelInstance()->id }}
            @endif
        </div>
        <div class="flex justify-end gap-2 !text-sm">
            @foreach($manageableModel::getBrowseItemActions($manageableModel->getmodelInstance()) as $key => $action)
                @continue($key == 'edit')
                {!! $action->render() !!}
            @endforeach
        </div>
    </div>

    <form
        action="{{ route('wrla.manageable-models.upsert.post', [
            'modelUrlAlias' => $manageableModel->getUrlAlias(),
            'modelId' => $manageableModel->getmodelInstance()->id,
        ]) }}"
        enctype="multipart/form-data"
        method="POST"
        class="w-full">
        @csrf

        <div class="flex flex-col gap-6 mt-4 p-4 bg-slate-100 dark:bg-slate-700 shadow-slate-300 dark:shadow-slate-850 rounded-lg shadow-lg">
            @foreach($manageableModel->getManageableFields() as $manageableField)
                {!! $manageableField->renderParent($upsertType) !!}
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
                'color' => 'danger',
                'icon' => 'fa fa-times',
            ])
        </div>

    </form>

@endsection

@push('appendBody')
    {{-- Wysiwyg --}}
    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
    <script>
        ClassicEditor
            .create(document.querySelector( '#editor' ))
            .catch(error => {
                console.error( error );
            });
    </script>
@endpush