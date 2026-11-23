<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Display the public welcome page.
     */
    public function __invoke(): View
    {
        return view('welcome');
    }
}
