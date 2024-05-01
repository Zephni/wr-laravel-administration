<?php

namespace WebRegulate\LaravelAdministration\Classes;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class WRLARedirectException extends \Exception
{
    /**
     * Redirect URL
     *
     * @var string
     */
    protected ?string $redirectUrl;

    /**
     * WRLAException constructor.
     *
     * @param string $message
     * @param string|null $redirect
     */
    public function __construct(string $message, ?string $redirectUrl = null)
    {
        $this->redirectUrl = $redirectUrl;
        parent::__construct($message);
    }

    /**
     * Run the redirect.
     * 
     * @return void
     */
    public function redirect(): void
    {
        $redirectUrl = $this->redirectUrl ?? url()->previous();

        redirect($redirectUrl)
            ->withInput()
            ->with('error', "<b>Exception:</b> ".$this->getMessage())
            ->send();
        die();
    }
}