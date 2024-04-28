@themeComponent('forms.button', [
    'size' => 'small',
    'color' => 'primary',
    'type' => 'button',
    'text' => 'Restore',
    'icon' => 'fa fa-undo relative top-[-1px] !mr-0 text-xs',
    'attr' => [
        'title' => 'Restore',
        'wire:click' => 'restoreModel("'.$manageableModel::getUrlAlias().'", '.$manageableModel->getModelInstance()->id.')',
    ]
])
