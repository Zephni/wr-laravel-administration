<?php

namespace WebRegulate\LaravelAdministration\Classes;

use Illuminate\Support\Collection;
use WebRegulate\LaravelAdministration\Models\User;

class WRLAPermissions
{
    /**
     * Base manageable model
     * 
     * @var ManageableModel
     */
    protected ManageableModel $manageableModel;

    /**
     * User instance
     * 
     * @var User
     */
    protected User $user;

    /**
     * Permissions constants
     */
    const CREATE = 'create';
    const EDIT = 'edit';
    const BROWSE = 'browse';
    const DELETE = 'delete';
    const RESTORE = 'restore';

    /**
     * Permissions
     * 
     * @var array
     */
    protected array $permissions = [];

    /**
     * Constructor
     * 
     * @param ManageableModel $manageableModel
     */
    public function __construct(ManageableModel $manageableModel)
    {
        $this->user = User::current();
        $this->manageableModel = $manageableModel;
        $this->applyModelDefaultPermissions();
    }

    /**
     * Get user permissions specific to the manageable model
     *
     * @return void
     */
    public function applyModelDefaultPermissions()
    {
        // Get user decoded permissions
        $userPermissions = json_decode($this->user->permissions);

        // If user permissions doesn't have the model url alias key, then skip
        if(data_get($userPermissions, 'model.'.$this->manageableModel->getUrlAlias()) === null) {
            return;
        }

        // If found model url alias key, then apply permissions
        $this->permissions = (array)data_get($userPermissions, 'model.'.$this->manageableModel->getUrlAlias());
    }

    /**
     * Check if user has permission
     * 
     * @param string $permission
     * @return bool
     */
    public function hasPermission(string $permission, mixed $equalTo = true): bool
    {
        // If user is master, return true
        if($this->user->isMaster()) {
            return true;
        }

        $permission = data_get($this->permissions, $permission);
        return $permission === $equalTo;
    }
}