@extends('layouts.manager')

@use('App\Support\Money')

@section('title', 'Dashboard')

@section('content')
    <section class="page-heading">
        <h1>Dashboard</h1>
        <p>Your inventory value and recorded revenue and expenses at a glance.</p>
    </section>

    <section class="summary-grid" aria-label="Financial summary">
        <article class="summary-card">
            <span class="summary-label">Inventory valuation</span>
            <strong class="summary-value">{{ Money::format($valuation) }}</strong>
            <span class="summary-hint">Stock × purchase price</span>
        </article>
        <article class="summary-card">
            <span class="summary-label">Revenue</span>
            <strong class="summary-value">{{ Money::format($revenue) }}</strong>
            <span class="summary-hint">From outbound movements</span>
        </article>
        <article class="summary-card">
            <span class="summary-label">Expenses</span>
            <strong class="summary-value">{{ Money::format($expenses) }}</strong>
            <span class="summary-hint">From inbound movements</span>
        </article>
    </section>

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
                        <th scope="col">SKU</th>
                        <th scope="col" class="numeric">In stock</th>
                        <th scope="col" class="numeric">Min. level</th>
                        <th scope="col" class="numeric">Purchase price</th>
                        <th scope="col" class="numeric">Selling price</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr>
                            <td data-label="ID">{{ $product->productID }}</td>
                            <td data-label="Name" class="product-name">{{ $product->name }}</td>
                            <td data-label="SKU">{{ $product->stockKeepUnit }}</td>
                            <td data-label="In stock" class="numeric">
                                {{ $product->stockQuantity }}
                                @if ($product->isLowOnStock())
                                    <span class="badge-low">Low</span>
                                @endif
                            </td>
                            <td data-label="Min. level" class="numeric">{{ $product->minStockLevel }}</td>
                            <td data-label="Purchase price" class="numeric">{{ Money::format($product->purchasePrice) }}</td>
                            <td data-label="Selling price" class="numeric">{{ Money::format($product->sellingPrice) }}</td>
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
        @if ($errors->any() && ! $errors->has('q')) data-open-on-load @endif>
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
            <p class="modal-intro">An ID is assigned automatically once the item is saved.</p>

            <div class="form-grid">
                <div class="form-field span-2">
                    <label for="name">Product name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" maxlength="255" required
                        class="@error('name') input-error @enderror">
                    @error('name')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-field">
                    <label for="stockKeepUnit">Stock keeping unit (SKU)</label>
                    <input id="stockKeepUnit" name="stockKeepUnit" type="text" value="{{ old('stockKeepUnit') }}"
                        maxlength="100" required class="@error('stockKeepUnit') input-error @enderror">
                    @error('stockKeepUnit')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-field">
                    <label for="minStockLevel">Minimum stock level</label>
                    <input id="minStockLevel" name="minStockLevel" type="number" min="0" step="1"
                        inputmode="numeric" value="{{ old('minStockLevel', 0) }}" required
                        class="@error('minStockLevel') input-error @enderror">
                    @error('minStockLevel')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-field">
                    <label for="purchasePrice">Purchase price</label>
                    <input id="purchasePrice" name="purchasePrice" type="number" min="0" step="0.01"
                        inputmode="decimal" value="{{ old('purchasePrice') }}" required
                        class="@error('purchasePrice') input-error @enderror">
                    @error('purchasePrice')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-field">
                    <label for="sellingPrice">Selling price</label>
                    <input id="sellingPrice" name="sellingPrice" type="number" min="0" step="0.01"
                        inputmode="decimal" value="{{ old('sellingPrice') }}" required
                        class="@error('sellingPrice') input-error @enderror">
                    @error('sellingPrice')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="button-secondary" data-dialog-close>Cancel</button>
                <button type="submit" class="button-primary">Save item</button>
            </div>
        </form>
    </dialog>
@endsection
