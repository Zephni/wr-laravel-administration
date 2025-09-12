<?php

namespace WebRegulate\LaravelAdministration\Classes\NavigationItems;

use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;

class NavigationItemsManageableModels extends NavigationItem
{
    /**
     * Import all manageable models as array of nav items
     * 
     * @param mixed ...$manageableModelClasses
     */
    public static function import(... $manageableModelClasses): array
    {
        return self::getManageableModelsNavigationItems($manageableModelClasses);
    }

    /**
     * Retrieves an array of navigation items for all manageable models.
     *
     * @return array An array of NavigationItemManageableModel objects representing the navigation items.
     */
    public static function getManageableModelsNavigationItems(... $manageableModelClasses): array
    {
        $manageableModelClasses = WRLAHelper::flattenArray($manageableModelClasses);

        $navItems = [];

        if (empty(ManageableModel::$manageableModels)) {
            return $navItems;
        }

        // If passed manageable model classes array is empty, get all manageable model classes
        if (empty($manageableModelClasses)) {
            $manageableModelClasses = array_keys(WRLAHelper::$globalManageableModelData);
        }

        foreach ($manageableModelClasses as $manageableModelClass) {
            $nimm = $manageableModelClass::getNavigationItem();

            if (WRLAHelper::$globalManageableModelData[ltrim((string) $manageableModelClass, '\\')]['hideFromNavigation']) {
                continue;
            }

            $navItems[] = $nimm;
        }

        return $navItems;
    }
}
