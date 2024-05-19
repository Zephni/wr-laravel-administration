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

        // Get child navigation from model
        $childNavigationItems = $this->manageableModelClass::getChildNavigationItems();

        parent::__construct(
            'wrla.manageable-models.browse',
            ['modelUrlAlias' => $this->manageableModelClass::getUrlAlias()],
            $this->manageableModelClass::getDisplayName(true),
            $this->manageableModelClass::getIcon(),
            $childNavigationItems->toArray()
        );
    }
}
