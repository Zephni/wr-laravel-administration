@themeComponent('forms.button', [
    'size' => 'small',
    'color' => 'primary',
    'type' => 'button',
    'text' => 'Restore',
    'icon' => 'fa fa-undo relative top-[-1px] !mr-[3px] text-[10px]',
    'attr' => [
        'title' => 'Restore',
        'wire:click' => 'restoreModel("'.$manageableModel::getUrlAlias().'", '.$manageableModel->getModelInstance()->id.')',
    ]
])
