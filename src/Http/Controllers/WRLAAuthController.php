<?php

namespace WebRegulate\LaravelAdministration\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use WebRegulate\LaravelAdministration\Models\User;
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
        
        // For debugging, force login
        // $user = User::where('email', $request->input('email'))->first();
        // Auth::login($user);
        // return redirect()->route('wrla.dashboard');

        // Attempt login - OLD
        if (Auth::attempt($request->only('email', 'password'), $request->has('remember'))) {
            return redirect()->route('wrla.dashboard');
        }

        return redirect()->back()->withInput()->with('error', 'Invalid credentials, please try again');
    }

    /**
     * Forgot password view
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function forgotPassword(Request $request)
    {
        return view(WRLAHelper::getViewPath('auth.forgot-password', true));
    }

    /**
     * Forgot password post
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function forgotPasswordPost(Request $request)
    {
        // Get user appropriate validation rules
        $tempUser = new \App\WRLA\User();
        $validationRules = $tempUser->getValidationRules()->only(['email'])->toArray();

        // Validate
        $request->validate($validationRules);

        // Get user
        $user = User::where('email', $request->input('email'))->first();

        // Check user
        if (!$user) {
            return redirect()->back()->withInput()->with('error', 'We could not find a user with that email address');
        }

        // Create token and send the reset link to the user
        $token = Password::createToken($user);
        $user->sendPasswordResetNotification($token);

        // Return user with success
        return redirect()->route('wrla.login')->with('success', 'Password reset link has been sent to: <br />' . $user->email);
    }

    /**
     * Reset password view
     * @param Request $request
     * @param string $token
     * @return \Illuminate\Contracts\View\View | \Illuminate\Http\RedirectResponse
     */
    public function resetPassword(Request $request, $email, $token)
    {
        // Get user by email
        $user = User::where('email', $email)->first();

        // Check if token is valid
        if (!Password::tokenExists($user, $token)) {
            return redirect()->route('wrla.login')->with('error', 'Invalid token');
        }

        return view(WRLAHelper::getViewPath('auth.reset-password', true), [
            'email' => $user->email,
            'token' => $token,
        ]);
    }

    /**
     * Reset password post
     * @param Request $request
     * @param string $token
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resetPasswordPost(Request $request, $token)
    {
        // Get user appropriate validation rules
        $tempUser = new \App\WRLA\User();
        $validationRules = $tempUser->getValidationRules()->only(['email', 'password'])->toArray();
        $validationRules['password'] = str_replace('confirmed|', '', $validationRules['password']);

        // Validate
        $request->validate($validationRules);

        // Get user
        $user = User::where('email', $request->input('email'))->first();

        // Check user
        if (!$user) {
            return redirect()->back()->withInput()->with('error', 'We could not find a user with that email address');
        }

        // Reset password
        $user->password = Hash::make($request->input('password'));
        $user->save();

        // Return user with success
        return redirect()->route('wrla.login')->with('success', 'Password has been reset successfully');
    }
}
