<?php

namespace Zephni\WRLaravelAdministration\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class WRLaravelAdministrationAuthController extends Controller
{
    /**
     * Login view
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function login(Request $request)
    {
        return view('wr-laravel-administration::auth.login');
    }
}
