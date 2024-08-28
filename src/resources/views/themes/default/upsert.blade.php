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
            @foreach($manageableModel->getItemActions() as $key => $action)
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

        @php $hasWysiwyg = false; @endphp
        
        <div class="flex flex-wrap gap-6 mt-4 p-4 bg-slate-100 dark:bg-slate-700 shadow-slate-300 dark:shadow-slate-850 rounded-lg shadow-lg">
            @foreach($manageableModel->getManageableFields() as $manageableField)
                @php
                    // If any field is Wysiwyg, set $hasWysiwyg to true so we can run the JS script at the end of this file
                    if($manageableField->getType() == 'Wysiwyg') {
                        $hasWysiwyg = true;
                    }
                @endphp

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

@if(isset($hasWysiwyg) && $hasWysiwyg === true)
    @push('appendBody')
        {{-- Wysiwyg --}}
        {{-- <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
        <script>
            ClassicEditor
                .create(document.querySelector( '.wrla_wysiwyg' ))
                .catch(error => {
                    console.error( error );
                });
        </script> --}}

        <script src="https://cdn.tiny.cloud/1/126uh4v0nur2ag6fpa5vb60rduwp1skzx02vsmdww39mpva2/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
        <script>
            tinymce.init({
                selector: '.wrla_wysiwyg',
                plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount code paste fullscreen',
                toolbar: 'undo redo | blocks | bold italic underline | link media table | align | numlist bullist indent | code',
                paste_data_images: true,
                relative_urls : false
            });
        </script>
    @endpush
@endif