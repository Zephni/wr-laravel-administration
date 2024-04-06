<?php

namespace Zephni\WRLaravelAdministration\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class WRLAAuthController extends Controller
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

        // Attempt login
        if (auth()->attempt($request->only('email', 'password'))) {
            return redirect()->route('wrla.dashboard');
        }

        return redirect()->back()->withInput()->with('error', 'Invalid credentials, please try again');
    }
}
