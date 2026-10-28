<?php

namespace App\Http\Controllers;

use App\Application\Inventory\GetManagerDashboard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request, GetManagerDashboard $dashboard): RedirectResponse|View
    {
        $user = $request->user();

        if ($user->isManager()) {
            return redirect()->route('dashboard');
        }

        abort_if($user->companyID === null, 403);

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $search = trim($validated['q'] ?? '');

        return view('pages.employee.home', [
            ...$dashboard->execute($user->companyID, $search),
            'search' => $search,
        ]);
    }
}
