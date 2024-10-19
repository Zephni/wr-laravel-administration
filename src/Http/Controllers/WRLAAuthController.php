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
            // If wrla_impersonating_user is in session, forget it
            if($request->session()->has('wrla_impersonating_user')) {
                $request->session()->forget('wrla_impersonating_user');
            }

            return redirect()->route('wrla.dashboard');
        }

        return redirect()->back()->withInput()->with('error', 'Invalid credentials, please try again');
    }

    /**
     * Impersonate / Login as user
     *
     * @param Request $request
     * @param int $userId
     */
    public function impersonateLoginAs(Request $request, int $userId)
    {
        // Get current user id
        $origionalUserId = User::current()->id;

        // Get user by id
        $user = User::find($userId);

        // Check has impersonate permission
        if(!\App\WRLA\User::getPermission(\App\WRLA\User::IMPERSONATE)) {
            return redirect()->route('wrla.dashboard')->with('error', "You do not have permission to login as another user.");
        }

        // Check user exists
        if($user == null) {
            return redirect()->route('wrla.dashboard')->with('error', "User with ID `$userId` not found.");
        }

        // Login as user
        Auth::login($user);

        // Set wrla_impersonating_user in session to original user id
        session()->put('wrla_impersonating_user', $origionalUserId);

        // If user has admin privilege redirect to dashboard, otherwise redirect to frontend
        if($user->isAdmin()) {
            return redirect()->route('wrla.dashboard');
        } else {
            return redirect('/');
        }
    }

    /**
     * Impersonate / Switch back
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function impersonateSwitchBack(Request $request)
    {
        // If wrla_impersonating_user is not in session, return invalid request
        if (!$request->session()->has('wrla_impersonating_user')) {
            abort(403, 'Invalid request');
        }

        // Get original user id
        $origionalUserId = $request->session()->get('wrla_impersonating_user');

        // Logout current user
        Auth::logout();

        // Login as original user
        Auth::login(User::find($origionalUserId));

        // Forget wrla_impersonating_user from session
        $request->session()->forget('wrla_impersonating_user');

        // Redirect back
        return redirect()->back();
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
        // Get wrla user instance
        $userInstance = \App\WRLA\User::make();

        // Get user email validation rule without unique or confirmed
        $forgotPasswordValidation = WRLAHelper::removeRuleFromValidationString(
            ['unique:users,email,', 'confirmed'],
            $userInstance->getValidationRule('email')
        );

        // Validate
        $request->validate([
            'email' => $forgotPasswordValidation
        ]);

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
        // Get wrla user instance
        $userInstance = \App\WRLA\User::make();

        // Get user email validation rules without unique (Note we do not have to include the id here because we are using a blank user)
        $resetPasswordValidation = WRLAHelper::removeRuleFromValidationString(
            'unique:users,email,',
            $userInstance->getValidationRule('email')
        );

        // Validate
        $request->validate([
            'email' => $resetPasswordValidation
        ]);

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
        return redirect()->route('wrla.login')->withInput()->with('success', 'Password has been reset successfully');
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

        // If wrla_impersonating_user is in session, forget it
        if($request->session()->has('wrla_impersonating_user')) {
            $request->session()->forget('wrla_impersonating_user');
        }

        // Redirect to login
        return redirect()->route('wrla.login');
    }
}
