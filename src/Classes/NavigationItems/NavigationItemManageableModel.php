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
        // Error handling
        throw_if(!class_exists($manageableModelClass), new \Exception("Model class `$manageableModelClass` does not exist when passing to navigation item."));
        throw_if(!is_subclass_of($this->manageableModelClass, 'WebRegulate\LaravelAdministration\Classes\ManageableModel'), new \Exception("Model class `$this->manageableModelClass` must extend ManageableModel when passing to navigation item."));

        // Get child navigation from model
        // $childNavigationItems = $this->manageableModelClass::getChildNavigationItems();

        $manageableModelClass::staticSetup();

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
