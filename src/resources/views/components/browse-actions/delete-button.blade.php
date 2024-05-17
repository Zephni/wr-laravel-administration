@themeComponent('forms.button', [
    'size' => 'small',
    'type' => 'button',
    'color' => 'danger',
    'text' => $text ?? 'Delete',
    'icon' => 'fa fa-trash relative mt-[1px] !mr-[3px] text-[10px]',
    'class' => 'bg-red-500 hover:bg-red-600 text-white',
    'attributes' => new \Illuminate\View\ComponentAttributeBag([
        'title' => $text ?? 'Delete',
        'onclick' => "confirm('Are you sure?') || event.stopImmediatePropagation()",
        'wire:click' => 'deleteModel('.$manageableModel->getModelInstance()->id.', '.($permanent ?? false ? '1' : '0').')',
    ])
])
