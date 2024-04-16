<div>
    @if($model->id == null)
        Creating new {{ $manageableModelClass::getDisplayName() }}
    @else
        Editing {{ $manageableModelClass::getDisplayName() }}, ID: {{ $model->id }}
    @endif
</div>
