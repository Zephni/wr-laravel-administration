<?php
    // // If browse page
    // if($WRLAHelper::getCurrentPageType() == WebRegulate\LaravelAdministration\Enums\PageType::BROWSE) {
    //     $attributeBag = new \Illuminate\View\ComponentAttributeBag([
    //         'title' => $text ?? 'Delete',
    //         'wire:click' => 'deleteModel('.$manageableModel->getModelInstance()->id.')',
    //         'wire:confirm' => 'Are you sure?',
    //     ]);
    // // If edit page
    // } else if ($WRLAHelper::getCurrentPageType() == WebRegulate\LaravelAdministration\Enums\PageType::EDIT) {
    //     $attributeBag = new \Illuminate\View\ComponentAttributeBag([
    //         'title' => $text ?? 'Delete',
    //         'onclick' => "
    //             if(confirm('Are you sure?')){
    //                 window.location.href = '".route('wrla.manageable-models.browse', [
    //                     'modelUrlAlias' => $manageableModel->getUrlAlias(),
    //                     'delete' => $manageableModel->getModelInstance()->id
    //                 ])."';
    //             } else {
    //                 event.stopImmediatePropagation();
    //             }
    //         "
    //     ]);
    // // Else
    // } else {
    //     $attributeBag = new \Illuminate\View\ComponentAttributeBag([]);
    // }

    // For now we have had to remove the conditional logic as when livewire re-renders the page Route::is is not longer possible to use
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
