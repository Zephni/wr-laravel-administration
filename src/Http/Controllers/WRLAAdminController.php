<?php

namespace WebRegulate\LaravelAdministration\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

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
        return view(WRLAHelper::getViewPath('livewire-content'), [
            'livewireComponentAlias' => 'wrla.manageable-models.browse',
            'livewireComponentData' => [
                'modelUrlAlias' => $modelUrlAlias
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
        return view(WRLAHelper::getViewPath('livewire-content'), [
            'livewireComponentAlias' => 'wrla.manageable-models.upsert',
            'livewireComponentData' => [
                'modelUrlAlias' => $modelUrlAlias,
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
