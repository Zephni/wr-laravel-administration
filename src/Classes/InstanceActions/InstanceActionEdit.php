<?php

namespace WebRegulate\LaravelAdministration\Classes\InstanceActions;

use WebRegulate\LaravelAdministration\Classes\InstanceAction;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;
use WebRegulate\LaravelAdministration\Enums\ManageableModelPermissions;

class InstanceActionEdit
{
    public static function make(ManageableModel $manageableModel, string $modelUrlAlias, int|null $modelId): InstanceAction
    {
        return InstanceAction::make($manageableModel, 'Edit', 'fa fa-edit', 'primary')
            ->enableOnCondition($manageableModel::getPermission(ManageableModelPermissions::EDIT))
            ->setAction(route('wrla.manageable-models.edit', [
                'modelUrlAlias' => $modelUrlAlias,
                'id' => $modelId,
            ]));
    }
}
