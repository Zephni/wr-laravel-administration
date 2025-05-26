<?php

namespace WebRegulate\LaravelAdministration\Classes\NavigationItems;

use Illuminate\Support\Facades\Route;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Enums\ManageableModelPermissions;
use WebRegulate\LaravelAdministration\Enums\PageType;

class NavigationItemManageableModel extends NavigationItem
{
    public function __construct(
        public string $manageableModelClass,
    ) {
        // Error handling
        throw_if(! class_exists($manageableModelClass), new \Exception("Model class `$manageableModelClass` does not exist when passing to navigation item."));
        throw_if(! is_subclass_of($this->manageableModelClass, \WebRegulate\LaravelAdministration\Classes\ManageableModel::class), new \Exception("Model class `$this->manageableModelClass` must extend ManageableModel when passing to navigation item."));

        // Get child navigation from model
        // $childNavigationItems = $this->manageableModelClass::getChildNavigationItems();

        parent::__construct(
            'wrla.manageable-models.browse',
            ['modelUrlAlias' => $this->manageableModelClass::getUrlAlias()],
            $this->manageableModelClass::getDisplayName(true),
            $this->manageableModelClass::getIcon(),
            $this->manageableModelClass::getChildNavigationItems()->toArray()
        );
    }

    /**
     * Is active
     */
    public function isChildActive(): bool
    {
        // If current page is edit
        if (WRLAHelper::getCurrentPageType() === PageType::EDIT) {
            // If modelUrlAlias doesn't exist, return false
            if (! isset(Route::current()->parameters['modelUrlAlias']) || ! isset($this->routeData['modelUrlAlias'])) {
                return false;
            }

            // If the current route data modelUrlAlias is same as $this->routeData
            if (Route::current()->parameters['modelUrlAlias'] == $this->routeData['modelUrlAlias']) {
                return true;
            }
        }

        return parent::isChildActive();
    }

    /**
     * Check show condition
     */
    public function checkShowCondition(): bool
    {
        if (
            !$this->manageableModelClass::getPermission(ManageableModelPermissions::ENABLED)
            || !$this->manageableModelClass::getPermission(ManageableModelPermissions::SHOW_IN_NAV)
        ) {
            return false;
        }

        return parent::checkShowCondition();
    }
}
