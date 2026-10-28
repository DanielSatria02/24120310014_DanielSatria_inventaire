<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request): RedirectResponse|View
    {
        if ($request->user()->isManager()) {
            return redirect()->route('dashboard');
        }

        return view('home');
    }
}
