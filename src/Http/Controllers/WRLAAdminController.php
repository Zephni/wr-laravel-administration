<?php

namespace WebRegulate\LaravelAdministration\Http\Controllers;

use App\WRLA\User;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use WebRegulate\LaravelAdministration\Enums\PageType;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;

/**
 * Class WRLAAdminController
 *
 * This class is responsible for handling the administration routes and actions in the Laravel application.
 * It extends the base Controller class and provides methods for managing the dashboard, browsing and upserting manageable models, and logging out.
 *
 * @package WebRegulate\LaravelAdministration\Http\Controllers
 */
class WRLAAdminController extends Controller
{
    /**
     * index / dashboard view
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request)
    {
        return view(WRLAHelper::getViewPath('dashboard'));
    }

    /**
     * ManageableModel browse view
     * @param Request $request
     * @param string $modelUrlAlias
     * @return View | RedirectResponse
     */
    public function browse(Request $request, string $modelUrlAlias): View | RedirectResponse
    {
        // Get the manageable model class by its URL alias
        $manageableModelClass = ManageableModel::getByUrlAlias($modelUrlAlias);

        // If the manageable model is null, redirect to the dashboard with error
        if (is_null($manageableModelClass)) {
            return redirect()->route('wrla.dashboard')->with('error', "Manageable model with url alias `$modelUrlAlias` not found.");
        }

        return view(WRLAHelper::getViewPath('livewire-content'), [
            'title' => 'Browse ' . $manageableModelClass::getDisplayName(),
            'livewireComponentAlias' => 'wrla.manageable-models.browse',
            'livewireComponentData' => [
                'manageableModelClass' => $manageableModelClass
            ]
        ]);
    }

    /**
     * ManageableModel upsert view
     * @param Request $request
     * @param string $modelUrlAlias
     * @param ?int $id
     * @return View | RedirectResponse
     */
    public function upsert(Request $request, string $modelUrlAlias, ?int $modelId = null): View | RedirectResponse
    {
        // Get the manageable model by its URL alias
        $manageableModelClass = ManageableModel::getByUrlAlias($modelUrlAlias);

        // If the manageable model is null, redirect to the dashboard with error
        if (is_null($manageableModelClass)) {
            return redirect()->route('wrla.dashboard')->with('error', "Manageable model with url alias `$modelUrlAlias` not found.");
        }

        // Get manageable model instance
        $manageableModel = $modelId == null
            ? new $manageableModelClass()
            : new $manageableModelClass($modelId);

        return view(WRLAHelper::getViewPath('upsert'), [
            'upsertType' => $modelId ? PageType::EDIT : PageType::CREATE,
            'manageableModel' => $manageableModel
        ]);
    }

    /**
     * ManageableModel upsert submit
     * @param Request $request
     * @param string $modelUrlAlias
     * @param ?int $id
     * @return RedirectResponse
     */
    public function upsertPost(Request $request, string $modelUrlAlias, ?int $modelId = null): RedirectResponse
    {
        try {
            // Get manageable model class by its URL alias
            $manageableModelClass = ManageableModel::getByUrlAlias($modelUrlAlias);

            // Check model class exists
            if (is_null($manageableModelClass) || !class_exists($manageableModelClass)) {
                return redirect()->route('wrla.dashboard')->with('error', "Manageable model `$manageableModelClass` not found.");
            }

            if($modelId == null)
            {
                // Create new model instance
                $manageableModel = new $manageableModelClass;
            }
            else
            {
                // Get model by it's id
                $manageableModel =  new $manageableModelClass($modelId);

                // Check model id exists
                if ($manageableModel == null) {
                    return redirect()->route('wrla.dashboard')->with('error', "Model ".$manageableModelClass." with ID `$modelId` not found.");
                }
            }

            // Get validation rules for this model
            $rules = $manageableModel->getValidationRules()->toArray();

            // Validate
            $formKeyValues = $request->validate($rules);

            // Update only changed values on the model instance
            $manageableModel->updateModelInstanceProperties($request, $manageableModel->getManageableFields(), $formKeyValues);

            // Save the model
            $manageableModel->getmodelInstance()->save();
        } catch (\Exception $e) {
            // Log error
            Log::channel('wrla')->error('Error saving model: ' . $e->getMessage());

            // Redirect with error
            return redirect()->route('wrla.manageable-model.edit', [
                'modelUrlAlias' => $manageableModel->getUrlAlias(),
                'id' => $manageableModel->getmodelInstance()->id
            ])->with('error', 'Error saving model (see wrla.log for details): ' . $e->getMessage());
        }

        // Redirect to the browse page
        return redirect()->route('wrla.manageable-model.edit', [
            'modelUrlAlias' => $manageableModel->getUrlAlias(),
            'id' => $manageableModel->getmodelInstance()->id
        ])->with('success', 'Saved '.$manageableModel->getDisplayName().' successfully.');
    }

    /**
     * Manage account view
     *
     * @param Request $request
     * @return View
     */
    public function manageAccount(Request $request): View
    {
        // Get manageable model instance
        $manageableModel = User::current();

        return view(WRLAHelper::getViewPath('manage-account'), [
            'upsertType' => PageType::EDIT,
            'manageableModel' => $manageableModel
        ]);
    }

    /**
     * logout
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        // Logout
        Auth::logout();

        // Redirect to login
        return redirect()->route('wrla.login');
    }
}
