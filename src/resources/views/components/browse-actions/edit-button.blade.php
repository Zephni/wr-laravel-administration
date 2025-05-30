@themeComponent('forms.button', [
    'href' => route('wrla.manageable-models.edit', [
        'modelUrlAlias' => $manageableModel::getUrlAlias(),
        'id' => $manageableModel->getModelInstance()->id
    ]),
    'size' => 'small',
    'color' => 'primary',
    'type' => 'button',
    'text' => 'Edit',
    'icon' => 'fa fa-edit relative top-[-1px] !mr-[3px] text-[10px]',
    'attributes' => Arr::toAttributeBag([
        'title' => 'Edit'
    ])
])
