<div>
    @if($manageableModel->modelInstance->id == null)
        Creating new {{ $manageableModel->getDisplayName() }}
    @else
        Editing {{ $manageableModel->getDisplayName() }}, ID: {{ $manageableModel->modelInstance->id }}
    @endif

    <div class="flex flex-col gap-4 mt-12 p-4 bg-slate-100 dark:bg-slate-700 shadow-slate-300 dark:shadow-slate-950 rounded-lg shadow-lg">
        @foreach($manageableModel->getManageableFields() as $manageableField)
            {!! $manageableField->render() !!}
        @endforeach
    </div>
</div>
