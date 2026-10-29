@extends('layouts.manager')

@use('App\Support\Money')

@section('title', 'Dashboard')

@section('content')
    @php
        $addFailed = $errors->addProduct->any();
        $addValues = [
            'name' => $addFailed ? old('name') : '',
            'categoryID' => $addFailed ? old('categoryID') : '',
            'sellingPrice' => $addFailed ? old('sellingPrice') : '',
            'minStockLevel' => $addFailed ? old('minStockLevel') : 0,
        ];
    @endphp

    <section class="page-heading">
        <h1>Dashboard</h1>
        <p>Your inventory value and recorded revenue and expenses at a glance.</p>
    </section>

    @include('partials.summary-cards')
    @include('partials.insight-panels')

    <section class="panel" aria-label="Products">
        <div class="toolbar">
            <form method="GET" action="{{ route('dashboard') }}" class="search-form" role="search">
                <label for="product-search" class="sr-only">Search products by name</label>
                <span class="search-field">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="7" />
                        <path d="M20 20l-3.5-3.5" />
                    </svg>
                    <input id="product-search" type="search" name="q" value="{{ $search }}"
                        maxlength="100" placeholder="Search products by name" autocomplete="off">
                </span>
                <button type="submit" class="button-secondary search-button">Search</button>
            </form>

            <button type="button" class="button-primary add-button" data-dialog-open="add-item-dialog">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
                    stroke-width="2.2" stroke-linecap="round" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                <span>Add item</span>
            </button>
        </div>

        @error('q')
            <p class="field-error panel-error">{{ $message }}</p>
        @enderror

        @if ($errors->deleteProduct->has('product'))
            <div class="notice panel-error" role="alert">{{ $errors->deleteProduct->first('product') }}</div>
        @endif

        @if ($products->isEmpty())
            <div class="empty-state">
                @if ($search !== '')
                    <p class="empty-title">No products match “{{ $search }}”.</p>
                    <p>Try a different name, or <a href="{{ route('dashboard') }}">clear the search</a>.</p>
                @else
                    <p class="empty-title">No products yet.</p>
                    <p>Add your first item to start tracking stock and value.</p>
                @endif
            </div>
        @else
            <table class="product-table">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Name</th>
                        <th scope="col">Category</th>
                        <th scope="col" class="numeric">In stock</th>
                        <th scope="col" class="numeric">Min. level</th>
                        <th scope="col" class="numeric">Selling price</th>
                        <th scope="col" class="numeric">Latest purchase price</th>
                        <th scope="col" class="numeric">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr>
                            <td data-label="ID">{{ $product->productID }}</td>
                            <td data-label="Name" class="product-name">{{ $product->name }}</td>
                            <td data-label="Category">{{ $product->category?->name ?? '—' }}</td>
                            <td data-label="In stock" class="numeric">
                                {{ $product->stockQuantity }}
                                @if ($product->isLowOnStock())
                                    <span class="badge-low">Low</span>
                                @endif
                            </td>
                            <td data-label="Min. level" class="numeric">{{ $product->minStockLevel }}</td>
                            <td data-label="Selling price" class="numeric">{{ Money::format($product->sellingPrice) }}</td>
                            <td data-label="Latest purchase price" class="numeric">
                                {{ $product->purchasePrice > 0 ? Money::format($product->purchasePrice) : '—' }}
                            </td>
                            <td data-label="Actions" class="numeric">
                                <div class="row-actions">
                                    <button type="button" class="button-outline"
                                        data-dialog-open="edit-product-{{ $product->productID }}">Edit</button>
                                    <button type="button" class="button-danger"
                                        data-dialog-open="delete-product-{{ $product->productID }}">Delete</button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($products->hasPages())
                <div class="pagination-wrap">{{ $products->links() }}</div>
            @endif
        @endif
    </section>

    <dialog id="add-item-dialog" class="modal" aria-labelledby="add-item-title"
        @if ($addFailed) data-open-on-load @endif>
        <form method="POST" action="{{ route('products.store') }}" class="modal-body">
            @csrf

            <div class="modal-head">
                <h2 id="add-item-title">Add item</h2>
                <button type="button" class="icon-button" data-dialog-close aria-label="Close">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <path d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            </div>
            <p class="modal-intro">
                An ID is assigned automatically. Stock arrives when your team records a restock, and the
                purchase price comes from that restock.
            </p>

            @include('partials.product-fields', [
                'prefix' => 'add',
                'bag' => 'addProduct',
                'values' => $addValues,
                'categories' => $categories,
            ])

            <div class="modal-actions">
                <button type="button" class="button-secondary" data-dialog-close>Cancel</button>
                <button type="submit" class="button-primary">Save item</button>
            </div>
        </form>
    </dialog>

    @foreach ($products as $product)
        @php
            $editFailed = (int) old('editProductID') === $product->productID && $errors->editProduct->any();
            $editValues = [
                'name' => $editFailed ? old('name') : $product->name,
                'categoryID' => $editFailed ? old('categoryID') : $product->categoryID,
                'sellingPrice' => $editFailed ? old('sellingPrice') : $product->sellingPrice,
                'minStockLevel' => $editFailed ? old('minStockLevel') : $product->minStockLevel,
            ];
            $deleteFailed = session('deleteProductID') === $product->productID && $errors->deleteProduct->has('password');
        @endphp

        <dialog id="edit-product-{{ $product->productID }}" class="modal"
            aria-labelledby="edit-title-{{ $product->productID }}" @if ($editFailed) data-open-on-load @endif>
            <form method="POST" action="{{ route('products.update', $product) }}" class="modal-body">
                @csrf
                @method('PUT')
                <input type="hidden" name="editProductID" value="{{ $product->productID }}">

                <div class="modal-head">
                    <h2 id="edit-title-{{ $product->productID }}">Edit item</h2>
                    <button type="button" class="icon-button" data-dialog-close aria-label="Close">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" aria-hidden="true">
                            <path d="M6 6l12 12M18 6L6 18" />
                        </svg>
                    </button>
                </div>
                <p class="modal-intro">Item #{{ $product->productID }} · {{ $product->stockQuantity }} in stock.
                    Stock changes only through recorded movements.</p>

                @include('partials.product-fields', [
                    'prefix' => 'edit-'.$product->productID,
                    'bag' => 'editProduct',
                    'values' => $editValues,
                    'categories' => $categories,
                ])

                <div class="modal-actions">
                    <button type="button" class="button-secondary" data-dialog-close>Cancel</button>
                    <button type="submit" class="button-primary">Save changes</button>
                </div>
            </form>
        </dialog>

        <dialog id="delete-product-{{ $product->productID }}" class="modal"
            aria-labelledby="delete-title-{{ $product->productID }}" @if ($deleteFailed) data-open-on-load @endif>
            <form method="POST" action="{{ route('products.destroy', $product) }}" class="modal-body">
                @csrf
                @method('DELETE')

                <div class="modal-head">
                    <h2 id="delete-title-{{ $product->productID }}">Delete item</h2>
                    <button type="button" class="icon-button" data-dialog-close aria-label="Close">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" aria-hidden="true">
                            <path d="M6 6l12 12M18 6L6 18" />
                        </svg>
                    </button>
                </div>
                <p class="modal-intro">
                    This permanently removes <strong>{{ $product->name }}</strong>. Enter your password to confirm.
                </p>

                <div class="form-field">
                    <label for="delete-password-{{ $product->productID }}">Your password</label>
                    <input id="delete-password-{{ $product->productID }}" name="password" type="password" required
                        autocomplete="current-password" @class(['input-error' => $deleteFailed])>
                    @if ($deleteFailed)
                        <p class="field-error">{{ $errors->deleteProduct->first('password') }}</p>
                    @endif
                </div>

                <div class="modal-actions">
                    <button type="button" class="button-secondary" data-dialog-close>Cancel</button>
                    <button type="submit" class="button-danger button-danger-solid">Delete item</button>
                </div>
            </form>
        </dialog>
    @endforeach
@endsection
