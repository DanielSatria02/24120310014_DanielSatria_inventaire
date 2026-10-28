<?php

namespace App\Http\Controllers\Inventory;

use App\Application\Inventory\GetManagerDashboard;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, GetManagerDashboard $dashboard): View
    {
        $user = $request->user();

        abort_if($user->companyID === null, 403);

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $search = trim($validated['q'] ?? '');

        return view('pages.manager.dashboard', [
            ...$dashboard->execute($user->companyID, $search),
            'search' => $search,
        ]);
    }
}
