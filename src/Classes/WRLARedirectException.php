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
    protected string $redirectUrl;

    /**
     * WRLAException constructor.
     *
     * @param string $message
     * @param string|null $redirect
     */
    public function __construct(string $message, string $redirectUrl)
    {
        $this->redirectUrl = $redirectUrl;
        parent::__construct($message);
    }

    /**
     * Run the redirect.
     * 
     * @return RedirectResponse
     */
    public function redirect(): RedirectResponse
    {
        return redirect($this->redirectUrl)
            ->withInput()
            ->with('error', "<b>Exception:</b> ".$this->getMessage())
            ->send();
    }
}