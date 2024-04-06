<?php

namespace WebRegulate\LaravelAdministration\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

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
}
