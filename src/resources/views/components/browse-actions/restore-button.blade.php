@themeComponent('forms.button', [
    'size' => 'small',
    'color' => 'primary',
    'type' => 'button',
    'text' => 'Restore',
    'icon' => 'fa fa-undo relative !mr-[3px] text-[10px]',
    'attributes' => Arr::toAttributeBag([
        'title' => 'Restore',
        'wire:click' => 'restoreModel('.$manageableModel->getModelInstance()->id.')',
    ])
])
