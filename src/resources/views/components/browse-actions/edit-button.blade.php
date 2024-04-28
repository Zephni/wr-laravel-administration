@themeComponent('forms.button', [
    'href' => route('wrla.manageable-model.edit', [
        'modelUrlAlias' => $manageableModel::getUrlAlias(),
        'id' => $manageableModel->getModelInstance()->id
    ]),
    'size' => 'small',
    'color' => 'primary',
    'type' => 'button',
    'text' => 'Edit',
    'icon' => 'fa fa-edit relative top-[-1px] !mr-0 text-xs',
    'attr' => [
        'title' => 'Edit'
    ]
])
