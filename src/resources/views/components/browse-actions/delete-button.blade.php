<?php
    // If browse page
    if(Route::currentRouteName() == 'wrla.manageable-model.browse') {
        $attributeBag = new \Illuminate\View\ComponentAttributeBag([
            'title' => $text ?? 'Delete',
            'onclick' => "confirm('Are you sure?') || event.stopImmediatePropagation();",
            'wire:click' => 'deleteModel('.$manageableModel->getModelInstance()->id.', '.($permanent ?? false ? '1' : '0').')',
        ]);
    // If edit page
    } else if (Route::currentRouteName() == 'wrla.manageable-model.edit') {
        $attributeBag = new \Illuminate\View\ComponentAttributeBag([
            'title' => $text ?? 'Delete',
            'onclick' => "
                if(confirm('Are you sure?')){
                    window.location.href = '".route('wrla.manageable-model.browse', [
                        'modelUrlAlias' => $manageableModel->getUrlAlias(),
                        'delete' => $manageableModel->getModelInstance()->id
                    ])."';
                } else {
                    event.stopImmediatePropagation();
                }
            "
        ]);
    // Else
    } else {
        $attributeBag = new \Illuminate\View\ComponentAttributeBag([]);
    }
?>

@themeComponent('forms.button', [
    'size' => 'small',
    'type' => 'button',
    'color' => 'danger',
    'text' => $text ?? 'Delete',
    'icon' => 'fa fa-trash relative mt-[1px] !mr-[3px] text-[10px]',
    'class' => 'bg-red-500 hover:bg-red-600 text-white',
    'attributes' => $attributeBag
])
