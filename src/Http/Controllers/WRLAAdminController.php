<?php

namespace WebRegulate\LaravelAdministration\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

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

        return view(WRLAHelper::getViewPath('livewire-content'), [
            'title' => ($modelId ? 'Edit' : 'Create') . ' ' . $manageableModelClass::getDisplayName(),
            'livewireComponentAlias' => 'wrla.manageable-models.upsert',
            'livewireComponentData' => [
                'manageableModelClass' => $manageableModelClass,
                'modelId' => $modelId
            ]
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
