<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreProductRequest;
use Illuminate\Http\RedirectResponse;

class ProductController extends Controller
{
    public function store(StoreProductRequest $request): RedirectResponse
    {
        $request->user()->company->products()->create($request->validated());

        return redirect()
            ->route('dashboard')
            ->with('status', 'Product added.');
    }
}
