<?php

namespace WebRegulate\LaravelAdministration\Classes\InstanceActions;

use WebRegulate\LaravelAdministration\Classes\InstanceAction;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;
use WebRegulate\LaravelAdministration\Enums\ManageableModelPermissions;

class InstanceActionDuplicate
{
    public static function make(ManageableModel $manageableModel, ?string $modelUrlAlias = null, int|null $modelId = null): InstanceAction
    {
        $modelUrlAlias ??= $manageableModel::getUrlAlias();
        $modelId ??= $manageableModel->model()->id ?? null;

        return InstanceAction::make($manageableModel, 'Duplicate', 'fa fa-copy', 'secondary')
            ->requireCondition($manageableModel::getPermission(ManageableModelPermissions::CREATE))
            ->setAction(route('wrla.manageable-models.create', [
                'modelUrlAlias' => $modelUrlAlias,
                'wrlaDuplicateFrom' => $modelId,
            ]));
    }
}
