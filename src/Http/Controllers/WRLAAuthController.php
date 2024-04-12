<?php

namespace WebRegulate\LaravelAdministration\Http\Controllers;

use App\WRLA\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class WRLAAuthController extends Controller
{
    /**
     * Login view
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function login(Request $request)
    {
        // return view('wr-laravel-administration::auth.login');
        return view(WRLAHelper::getViewPath('auth.login', true));
    }

    /**
     * Login post
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function loginPost(Request $request)
    {
        // Validate
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Attempt login - OLD
        if (Auth::attempt($request->only('email', 'password'), $request->has('remember'))) {
            return redirect()->route('wrla.dashboard');
        }

        return redirect()->back()->withInput()->with('error', 'Invalid credentials, please try again');
    }
}
