<?php

namespace WebRegulate\LaravelAdministration\Classes\InstanceActions;

use WebRegulate\LaravelAdministration\Classes\InstanceAction;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;
use WebRegulate\LaravelAdministration\Enums\ManageableModelPermissions;

class InstanceActionRestore
{
    public static function make(ManageableModel $manageableModel): InstanceAction
    {
        $multiConfirmMessage = 'Are you sure you want to restore the selected items?';

        return InstanceAction::make($manageableModel, 'Restore', 'fa fa-undo', 'primary')
            ->requireCondition(
                $manageableModel::getPermission(ManageableModelPermissions::RESTORE)
                && $manageableModel->isModelSoftDeleted()
            )
            ->setAdditionalAttributes([
                'wire:click' => 'restoreModel(' . $manageableModel->model()->id . ')',
            ])
            ->multiAction(function (array $ids) use ($manageableModel) {
                // Check has restore permission
                if (! $manageableModel::getPermission(ManageableModelPermissions::RESTORE)) {
                    return 'You do not have permission to restore these items.';
                }

                $restored = 0;
                $failed = 0;

                $manageableModelClass = $manageableModel::class;
                $baseModelClass = $manageableModelClass::getStaticOption($manageableModelClass, 'baseModelClass');

                foreach ($ids as $id) {
                    $model = $baseModelClass::withTrashed()->find((int) $id);

                    if (! $model || ! method_exists($model, 'trashed') || ! $model->trashed()) {
                        $failed++;
                        continue;
                    }

                    try {
                        $model->restore();
                        $restored++;
                    } catch (\Throwable $e) {
                        $failed++;
                    }
                }

                $message = $restored.' '.str($manageableModel::getDisplayName())->plural($restored)->toString().' restored.';

                if ($failed > 0) {
                    $message .= ' '.$failed.' could not be restored.';
                }

                return $message;
            }, $multiConfirmMessage);
    }
}
