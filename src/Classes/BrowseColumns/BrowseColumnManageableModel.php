<?php

namespace WebRegulate\LaravelAdministration\Classes\BrowseColumns;

use WebRegulate\LaravelAdministration\Classes\BrowseColumns\BrowseColumnLink;

class BrowseColumnManageableModel extends BrowseColumnBase
{

    /**
     * Create a new instance of the class
     *
     * @param string|null $label
     * @param string $relatedIdColumn Note if we are using a relationship, we can use dot notation to access the related model's id through the relationship, eg. relatedModel.id
     * @param string $manageableModelClass
     * @return BrowseColumnLink
     */
    public static function make(?string $label, string $relatedIdColumn, string $manageableModel): BrowseColumnLink
    {
        return BrowseColumnLink::make($label, function($value, $model) use($manageableModel, $relatedIdColumn) {
            if(empty($value) || data_get($model, $relatedIdColumn) == null) {
                return null;
            }

            return [
                'url' => route('wrla.manageable-models.edit', [
                    'modelUrlAlias' => $manageableModel::getUrlAlias(),
                    'id' => data_get($model, $relatedIdColumn),
                ]),
                'text' => $value,
                'icon' => $manageableModel::getIcon().' text-xs'
            ];
        })->renderHtml(true);
    }
}
