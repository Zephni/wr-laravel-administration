<?php

namespace WebRegulate\LaravelAdministration\Classes\NavigationItems;

use WebRegulate\LaravelAdministration\Classes\ManageableModel;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class NavigationItemsAllManageableModels extends NavigationItem
{
    /**
     * Static method to be called in config, sets up call on boot for importing manageable models into navigation
     *
     * @return string
     */
    public static function import(): string
    {
        return 'WebRegulate\LaravelAdministration\Classes\NavigationItems\NavigationItemsAllManageableModels::getAllManageableModelsNavigationItems';
    }

    /**
     * Retrieves an array of navigation items for all manageable models.
     *
     * @return array An array of NavigationItemManageableModel objects representing the navigation items.
     */
    public static function getAllManageableModelsNavigationItems(): array
    {
        $navItems = [];
        $manageableModels = ManageableModel::$manageableModels;

        if(empty($manageableModels)) {
            return $navItems;
        }

        foreach ($manageableModels as $manageableModel) {
            $nimm = $manageableModel::getNavigationItem();

            if($nimm->manageableModelClass::$hideFromNavigation) {
                continue;
            }

            $navItems[] = $nimm;
        }

        return $navItems;
    }
}
