@themeComponent('forms.button', [
    'href' => route('wrla.manageable-models.edit', [
        'modelUrlAlias' => $manageableModel::getUrlAlias(),
        'id' => $manageableModel->getModelInstance()->id
    ]),
    'size' => 'small',
    'color' => 'primary',
    'type' => 'button',
    'text' => 'Edit',
    'icon' => 'fa fa-edit relativepx] !mr-[3px] text-[10px]',
    'attributes' => new \Illuminate\View\ComponentAttributeBag([
        'title' => 'Edit'
    ])
])
