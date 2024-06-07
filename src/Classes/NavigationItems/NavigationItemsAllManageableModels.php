<?php

namespace WebRegulate\LaravelAdministration\Classes\NavigationItems;

use WebRegulate\LaravelAdministration\Classes\ManageableModel;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class NavigationItemsAllManageableModels extends NavigationItem
{
    /**
     * Import all manageable models as array of nav items
     *
     * @return array
     */
    public static function import(): array
    {
        return self::getAllManageableModelsNavigationItems();
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

            if($nimm->manageableModelClass::getStaticOption('hideFromNavigation')) {
                continue;
            }

            $navItems[] = $nimm;
        }

        return $navItems;
    }
}
