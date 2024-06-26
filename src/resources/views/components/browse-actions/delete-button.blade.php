<?php
    // If browse page
    if($WRLAHelper::getCurrentPageType() == WebRegulate\LaravelAdministration\Enums\PageType::BROWSE) {
        $attributeBag = new \Illuminate\View\ComponentAttributeBag([
            'title' => $text ?? 'Delete',
            'wire:click' => 'deleteModel('.$manageableModel->getModelInstance()->id.', '.($permanent ?? false ? '1' : '0').')',
            'wire:confirm' => 'Are you sure?',
        ]);
    // If edit page
    } else if ($WRLAHelper::getCurrentPageType() == WebRegulate\LaravelAdministration\Enums\PageType::EDIT) {
        $attributeBag = new \Illuminate\View\ComponentAttributeBag([
            'title' => $text ?? 'Delete',
            'onclick' => "
                if(confirm('Are you sure?')){
                    window.location.href = '".route('wrla.manageable-models.browse', [
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
