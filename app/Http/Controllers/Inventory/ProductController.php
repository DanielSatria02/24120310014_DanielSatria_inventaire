<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreProductRequest;
use App\Http\Requests\Inventory\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProductController extends Controller
{
    public function store(StoreProductRequest $request): RedirectResponse
    {
        $request->user()->company->products()->create($request->validated());

        return redirect()
            ->route('dashboard')
            ->with('status', 'Product added.');
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        // Stock quantity is deliberately not editable here; it only changes through recorded movements.
        $product->update($request->validated());

        return redirect()
            ->route('dashboard')
            ->with('status', 'Product updated.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $manager = $request->user();

        abort_unless($product->companyID === $manager->companyID, 404);

        if (! Hash::check((string) $request->input('password'), $manager->password)) {
            return back()
                ->withErrors(['password' => 'That password is incorrect.'], 'deleteProduct')
                ->with('deleteProductID', $product->productID);
        }

        if ($product->stockMovements()->exists()) {
            return back()
                ->withErrors(
                    ['product' => $product->name.' has recorded stock movements and cannot be deleted.'],
                    'deleteProduct'
                );
        }

        $product->delete();

        return redirect()
            ->route('dashboard')
            ->with('status', 'Product removed.');
    }
}
