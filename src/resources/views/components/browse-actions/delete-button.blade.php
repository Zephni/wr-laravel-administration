@themeComponent('forms.button', [
    'size' => 'small',
    'type' => 'button',
    'color' => 'danger',
    'text' => $text ?? 'Delete',
    'icon' => 'fa fa-trash relative !mr-[3px] text-[10px]',
    'class' => 'bg-red-500 hover:bg-red-600 text-white',
    'attributes' => new \Illuminate\View\ComponentAttributeBag([
        'title' => $text ?? 'Delete',
        'x-on:click' => "
            if(confirm('Are you sure?')){
                \$dispatch('deleteModel', { 'modelUrlAlias': '".$manageableModel->getUrlAlias()."', 'id': ".$manageableModel->getModelInstance()->id." });
            } else {
                event.stopImmediatePropagation();
            }
        "
    ])
])
