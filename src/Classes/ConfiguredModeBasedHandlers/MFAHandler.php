<?php
namespace WebRegulate\LaravelAdministration\Classes\ConfiguredModeBasedHandlers;

use Illuminate\Support\Facades\Blade;
use PragmaRX\Google2FAQRCode\Google2FA;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Classes\ConfiguredModeBasedHandler;

class MFAHandler extends ConfiguredModeBasedHandler
{
    /**
     * Base configuration path
     */
    public function baseConfigurationPath(): string {
        return 'mfa';
    }

    /**
     * Generate secret key
     * @return array ['secretKey' => string, 'qrImage' => string]
     */
    public function generateSecretAndQRImage(string $userEmail): array {
        $result = match($this->mode) {

            // pragmarx/google2fa
            'pragmarx/google2fa' => function() use ($userEmail) {
                $google2fa = new Google2FA();

                $secretKey = $google2fa->generateSecretKey(32);

                return [
                    'secretKey' => $secretKey,
                    'qrImage' => $google2fa->getQRCodeInline(
                        config('app.name'),
                        $userEmail,
                        $secretKey
                    )
                ];
            },

            default => false,
        };

        if ($result === false) {
            throw new \Exception("Unsupported MFA mode: {$this->mode}");
        }   

        return $result();
    }

    /**
     * Render 2FA form initial setup
     */
    public function render2FAFormInitialSetup(string $email, string $password, string $qrImage, string $mfaSecret): string {
        return Blade::render(<<<BLADE
            <div style="display: flex; flex-direction: column; justify-content: center; align-items: center; row-gap: 20px; width: 100%; margin-bottom: 10px; color: #888888; text-align: center; padding: 0px 20px;">
                <div>
                    Please scan the QR code below using your prefered authenticator app.
                </div>
                <div>
                    {!! \$qrImage !!}
                </div>
                <div>
                    And then enter the one time password below.
                </div>
                <input type="text" name="mfa_code" placeholder="Enter MFA code" required autofocus style="width: 200px; padding: 5px 10px; border: 1px solid #CCCCCC; border-radius: 8px; text-align: center;" />
                <input type="hidden" name="email" value="{{ \$email }}" />
                <input type="hidden" name="password" value="{{ \$password }}" />
                <input type="hidden" name="mfa_secret_key" value="{{ \$mfaSecret }}" />
            </div>
        BLADE, [
            'email' => $email,
            'password' => $password,
            'qrImage' => $qrImage,
            'mfaSecret' => $mfaSecret,
        ]);
    }

    /**
     * Render 2FA form validation
     */
    public function render2FAValidationForm(string $email, string $password): string {
        return Blade::render(<<<BLADE
            <div style="display: flex; flex-direction: column; justify-content: center; align-items: center; row-gap: 20px; width: 100%; margin-bottom: 10px; color: #888888; text-align: center; padding: 0px 20px;">
                <div style="">
                    Please enter the one time password below from your prefered authenticator app.
                </div>
                <input type="text" name="mfa_code" placeholder="Enter MFA code" required autofocus style="width: 200px; padding: 5px 10px; border: 1px solid #CCCCCC; border-radius: 8px; text-align: center;" />
                <input type="hidden" name="email" value="{{ \$email }}" />
                <input type="hidden" name="password" value="{{ \$password }}" />
            </div>
        BLADE, [
            'email' => $email,
            'password' => $password,
        ]);
    }

    /**
     * Validate MFA code
     */
    public function validateMFACode(string $mfaCode, string $secretKey): bool {
        $result = match($this->mode) {

            // pragmarx/google2fa
            'pragmarx/google2fa' => function() use ($mfaCode, $secretKey) {
                $google2fa = new Google2FA();
                return $google2fa->verifyKey($secretKey, $mfaCode);
            },

            default => false,
        };

        if ($result === false) {
            throw new \Exception("Unsupported MFA mode: {$this->mode}");
        }

        return $result();
    }
}