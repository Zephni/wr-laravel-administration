<?php

namespace WebRegulate\LaravelAdministration\Classes\NavigationItems;

class NavigationItemManageableModel extends NavigationItem
{
    public function __construct(
        public string $manageableModelClass,
    ) {
        try {
            if(!class_exists($manageableModelClass)) {

            }
        } catch (\Exception $e) {
            return;
        }

        // Check that $modelClass extends ManageableModel
        if(!is_subclass_of($this->manageableModelClass, 'WebRegulate\LaravelAdministration\Classes\ManageableModel')) {
            throw new \Exception("Model class `$this->manageableModelClass` must extend ManageableModel when passing to navigation item.");
        }

        parent::__construct(
            'wrla.manageable-models.browse',
            ['modelUrlAlias' => $this->manageableModelClass::getUrlAlias()],
            $this->manageableModelClass::getDisplayName(true),
            $this->manageableModelClass::getIcon(),
            [
                new NavigationItem(
                    'wrla.manageable-models.browse',
                    ['modelUrlAlias' => $this->manageableModelClass::getUrlAlias()],
                    'Browse',
                    'fa fa-list'
                ),
                new NavigationItem(
                    'wrla.manageable-models.create',
                    ['modelUrlAlias' => $this->manageableModelClass::getUrlAlias()],
                    'Create',
                    'fa fa-plus'
                ),
            ]
        );
    }
}
