<?php

namespace WebRegulate\LaravelAdministration\Classes\NavigationItems;

use Illuminate\Support\Facades\Route;
use WebRegulate\LaravelAdministration\Enums\PageType;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

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

        // Static setup
        $this->manageableModelClass::staticSetup();

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

    /**
     * Is active
     * @return bool
     */
    public function isChildActive(): bool
    {
        // If current page is edit
        if(WRLAHelper::getCurrentPageType() === PageType::EDIT) {
            // If the current route data modelUrlAlias is same as $this->routeData
            if(Route::current()->parameters['modelUrlAlias'] == $this->routeData['modelUrlAlias']) {
                return true;
            }
        }
        
        return parent::isChildActive();
    }
}
