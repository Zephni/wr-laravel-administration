@themeComponent('forms.button', [
    'href' => route('wrla.manageable-model.edit', [
        'modelUrlAlias' => $manageableModel::getUrlAlias(),
        'id' => $manageableModel->getModelInstance()->id
    ]),
    'size' => 'small',
    'color' => 'primary',
    'type' => 'button',
    'text' => 'Edit',
    'icon' => 'fa fa-edit relative top-[-2px] !mr-[3px] text-[10px]',
    'attr' => [
        'title' => 'Edit'
    ]
])
