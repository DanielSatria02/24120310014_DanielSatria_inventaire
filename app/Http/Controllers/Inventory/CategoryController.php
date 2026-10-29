<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreCategoryRequest;
use App\Http\Requests\Inventory\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $categories = $request->user()->company
            ->categories()
            ->withCount('products')
            ->orderBy('name')
            ->get();

        return view('pages.manager.categories', ['categories' => $categories]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $request->user()->company->categories()->create($request->validated());

        return redirect()->route('categories.index')->with('status', 'Category added.');
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('categories.index')->with('status', 'Category updated.');
    }

    public function destroy(Request $request, Category $category): RedirectResponse
    {
        abort_unless($category->companyID === $request->user()->companyID, 404);

        // Products stay; they simply become uncategorised.
        DB::transaction(function () use ($category) {
            $category->products()->update(['categoryID' => null]);
            $category->delete();
        });

        return redirect()->route('categories.index')->with('status', 'Category removed.');
    }
}
