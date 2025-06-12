<?php

namespace WebRegulate\LaravelAdministration\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Password;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Classes\ConfiguredModeBasedHandlers\MFAHandler;

class WRLAAuthController extends Controller
{
    /**
     * Login view
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function login(Request $request)
    {
        return view(WRLAHelper::getViewPath('auth.login', true));
    }

    /**
     * Login post
     *
     * @return \Illuminate\Http\RedirectResponse|string
     */
    public function loginPost(Request $request)
    {
        // Apply captcha validation if enabled
        $captchaResult = WRLAHelper::applyCaptchaCheck($request);
        if ($captchaResult !== true) {
            if(!$request->has('mfa_code')) {
                $request->validate(
                    ['captcha.error' => 'required|captcha'],
                    ['captcha.error' => 'Captcha validation failed. Please try again.']
                );
            } else {
                $request->validate(
                    ['mfa_code' => 'required'],
                    ['mfa_code.required' => 'MFA code is required']
                );
            }
        }

        // Validate
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Handle MFA if enabled
        $mfaResult = $this->handleMFA($request, $request->get('email'), $request->get('password'));
        if(!empty($mfaResult)) {
            return $mfaResult;
        }

        // Attempt login
        if (WRLAHelper::getUserDataModelClass()::attemptLogin($request->get('email'), $request->get('password'), $request->has('remember'))) {
            // If wrla_impersonating_user is in session, forget it
            if ($request->session()->has('wrla_impersonating_user')) {
                $request->session()->forget('wrla_impersonating_user');
            }

            return redirect()->route('wrla.dashboard');
        }

        return redirect()->back()->withInput()->with('error', 'Invalid credentials, please try again');
    }

    /**
     * Handle MFA Step 1
     */
    protected function handleMFA(Request $request, string $email, string $password): mixed
    {
        $mfaHandler = new MFAHandler();

        // TODO: If MFA is not in use, return null
        if(!$mfaHandler->isEnabled()) {
            return null;
        }

        // Get user and user data
        $user = WRLAHelper::getUserDataModelClass()::getUserByEmail($email);

        // If user doesn't exist, redirect back with error
        if ($user === null) {
            return redirect()->back()->withInput()->with('error', 'Invalid credentials, please try again');
        }

        // If password is incorrect, redirect back with error
        if (!WRLAHelper::getUserDataModelClass()::checkPassword($email, $password)) {
            return redirect()->back()->withInput()->with('error', 'Invalid credentials, please try again');
        }

        // Get wrla user data
        $wrlaUserData = $user->wrlaUserData;

        // If wrla user data does not require MFA, return null
        if (empty($wrlaUserData) || $wrlaUserData->user_id == null || !$wrlaUserData->requiresMFA()) {
            return null;
        }

        // Get user's MFA secret key
        $secretKey = $wrlaUserData->getMFASecretKey();

        /* User does not yet have their secret key set and no MFA code passed
        -----------------------------------------------------------------*/
        if(empty($secretKey) && !$request->has('mfa_code')) {
            // Generate secret key and QR image
            $secretAndQrImage = $mfaHandler->generateSecretAndQRImage($email);

            // Render 2FA initial setup
            return redirect()->back()->with([
                'mfa' => $mfaHandler->render2FAFormInitialSetup($email, $password, $secretAndQrImage['qrImage'], $secretAndQrImage['secretKey']),
            ]);
        }
        /* User does not yet have their secret key set but has passed an MFA code
        ---------------------------------------------------------------*/
        elseif(empty($secretKey) && $request->has('mfa_code')) {
            // Get mfa code and secret key from request
            $mfaCode = $request->get('mfa_code');
            $secretKey = $request->get('mfa_secret_key');

            // If invalid, redirect back with error
            if (!$mfaHandler->validateMFACode($mfaCode, $secretKey)) {
                return redirect()->back()->withInput()->with('error', 'Invalid MFA code, please try again');
            }
            // If MFA code is valid, set the secret key and allow login process continue
            else {
                // Set secret key on wrla user data and save
                $wrlaUserData->setMFASecretKey($secretKey);
                $wrlaUserData->save();
                return null;
            }
        }
        /* User has a secret key set but no MFA code passed
        ---------------------------------------------------------------*/
        elseif(!empty($secretKey) && !$request->has('mfa_code')) {
            // Render MFA verify form
            return redirect()->back()->with([
                'mfa' => $mfaHandler->render2FAValidationForm($email, $password),
            ]);
        }
        /* User has a secret key set and has passed an MFA code
        ---------------------------------------------------------------*/
        elseif(!empty($secretKey) && $request->has('mfa_code')) {
            // Get mfa code from request
            $mfaCode = $request->get('mfa_code');

            // If invalid, redirect back with error
            if (!$mfaHandler->validateMFACode($mfaCode, $secretKey)) {
                return redirect()->back()->withInput()->with('error', 'Invalid MFA code, please try again');
            }
            // If MFA code is valid, allow login process continue
            else {
                return null;
            }
        }

        // Invalid request, redirect back with error
        return redirect()->back()->withInput()->with('error', 'Invalid MFA request, something went wrong');
    }

    /**
     * Impersonate / Login as user
     */
    public function impersonateLoginAs(Request $request, int $userId)
    {
        // Get current user data and user id
        $origionalUserId = WRLAHelper::getCurrentUser()?->id;

        // If null, redirect to login
        if ($origionalUserId === null) {
            return redirect()->route('wrla.login');
        }

        // Get user and user data by id
        $user = WRLAHelper::getUserModelClass()::find($userId);
        $userData = WRLAHelper::getUserDataModelClass()::where('user_id', $userId)->first();

        // Check has impersonate permission
        if (! \App\WRLA\User::getPermission(\App\WRLA\User::IMPERSONATE)) {
            return redirect()->route('wrla.dashboard')->with('error', 'You do not have permission to login as another user.');
        }

        // Check user exists
        if ($user == null) {
            return redirect()->route('wrla.dashboard')->with('error', "User with ID `$userId` not found.");
        }

        // Login as user
        Auth::login($user);

        // Set wrla_impersonating_user in session to original user id
        session()->put('wrla_impersonating_user', $origionalUserId);

        // Set wrla_impersonating_previous_url in session to url before this impersonate request
        session()->put('wrla_impersonating_previous_url', $request->headers->get('referer', route('wrla.dashboard')));

        // If user has admin privilege redirect to dashboard, otherwise redirect to frontend
        if ($userData !== null && $userData->isAdmin()) {
            return redirect()->route('wrla.dashboard');
        } else {
            return redirect('/');
        }
    }

    /**
     * Impersonate / Switch back
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function impersonateSwitchBack(Request $request)
    {
        // If wrla_impersonating_user is not in session, return invalid request
        if (! $request->session()->has('wrla_impersonating_user')) {
            abort(403, 'Invalid request');
        }

        // Get original user id
        $origionalUserId = $request->session()->get('wrla_impersonating_user');

        // Logout current user
        Auth::logout();

        // Login as original user
        Auth::login(WRLAHelper::getUserModelClass()::find($origionalUserId));

        // Forget wrla_impersonating_user from session
        $request->session()->forget('wrla_impersonating_user');

        // Redirect to wrla_impersonating_previous_url or back if not set
        return redirect()->to(
            $request->session()->get('wrla_impersonating_previous_url', route('wrla.dashboard'))
        )->with('success', 'Switched back to your original account.');
    }

    /**
     * Forgot password view
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function forgotPassword(Request $request)
    {
        return view(WRLAHelper::getViewPath('auth.forgot-password', true));
    }

    /**
     * Forgot password post
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function forgotPasswordPost(Request $request)
    {
        // Apply captcha validation if enabled
        $captchaResult = WRLAHelper::applyCaptchaCheck($request);
        if ($captchaResult !== true) {
            $request->validate(
                ['captcha.error' => 'required|captcha'],
                ['captcha.error' => 'Captcha validation failed. Please try again.']
            );
        }

        // Get wrla user instance
        $userInstance = \App\WRLA\User::make();

        // Get user email validation rule without unique or confirmed
        $forgotPasswordValidation = WRLAHelper::removeRuleFromValidationString(
            ['unique:users,email,', 'confirmed'],
            $userInstance->getValidationRule('email')
        );

        // Validate
        $request->validate([
            'email' => $forgotPasswordValidation,
        ]);

        // Get user
        $user = WRLAHelper::getUserModelClass()::where('email', $request->input('email'))->first();

        // Check user
        if (! $user) {
            return redirect()->back()->withInput()->with('error', 'We could not find a user with that email address');
        }

        // Create token and send the reset link to the user
        $token = Password::createToken($user);
        $user->sendPasswordResetNotification($token);

        // Return user with success
        return redirect()->route('wrla.login')->with('success', 'Password reset link has been sent to: <br />'.$user->email);
    }

    /**
     * Reset password view
     *
     * @param  string  $token
     * @return \Illuminate\Contracts\View\View | \Illuminate\Http\RedirectResponse
     */
    public function resetPassword(Request $request, $email, $token)
    {
        // Get user by email
        $user = WRLAHelper::getUserModelClass()::where('email', $email)->first();

        // Check if token is valid
        if (! Password::tokenExists($user, $token)) {
            return redirect()->route('wrla.login')->with('error', 'Invalid token');
        }

        return view(WRLAHelper::getViewPath('auth.reset-password', true), [
            'email' => $user->email,
            'token' => $token,
        ]);
    }

    /**
     * Reset password post
     *
     * @param  string  $token
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
            'email' => $resetPasswordValidation,
        ]);

        // Get user
        $user = WRLAHelper::getUserModelClass()::where('email', $request->input('email'))->first();

        // Check user
        if (! $user) {
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
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        // Logout
        Auth::logout();

        // If wrla_impersonating_user is in session, forget it
        if ($request->session()->has('wrla_impersonating_user')) {
            $request->session()->forget('wrla_impersonating_user');
        }

        // Redirect to login
        if(config('wr-laravel-administration.wrla_auth_routes_enabled')) {
            return redirect()->route('wrla.login');
        } {
            return redirect('/');
        }
    }
}
