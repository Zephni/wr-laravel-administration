@themeComponent('forms.button', [
    'size' => 'small',
    'type' => 'button',
    'color' => 'danger',
    'text' => $text ?? 'Delete',
    'icon' => 'fa fa-trash relative top-[-1px] !mr-0 text-xs',
    'class' => 'bg-red-500 hover:bg-red-600 text-white',
    'attr' => [
        'title' => $text ?? 'Delete',
        'onclick' => "confirm('Are you sure?') || event.stopImmediatePropagation()",
        'wire:click' => 'deleteModel("'.$manageableModel::getUrlAlias().'", '.$manageableModel->getModelInstance()->id.', '.($permanent ?? false ? '1' : '0').')',
    ]
])