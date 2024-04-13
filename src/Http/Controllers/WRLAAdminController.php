<?php

namespace WebRegulate\LaravelAdministration\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class WRLAAdminController extends Controller
{
    /**
     * index / dashboard view
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request)
    {
        return view('wr-laravel-administration::dashboard');
    }

    /**
     * ManageableModel browse view
     * @param Request $request
     * @param string $modelUrlAlias
     * @return View | RedirectResponse
     */
    public function browse(Request $request, string $modelUrlAlias): View | RedirectResponse
    {
        return view('wr-laravel-administration::dashboard');
        // return view('wr-laravel-administration::browse', [
        //     'modelUrlAlias' => $modelUrlAlias
        // ]);
    }

    /**
     * ManageableModel upsert view
     * @param Request $request
     * @param string $modelUrlAlias
     * @param ?int $id
     * @return View | RedirectResponse
     */
    public function upsert(Request $request, string $modelUrlAlias, ?int $id = null): View | RedirectResponse
    {
        return view('wr-laravel-administration::dashboard');
        // return view('wr-laravel-administration::upsert', [
        //     'modelUrlAlias' => $modelUrlAlias,
        //     'id' => $id
        // ]);
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
