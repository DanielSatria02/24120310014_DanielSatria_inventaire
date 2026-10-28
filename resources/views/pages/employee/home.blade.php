@extends('layouts.manager')

@use('App\Support\Money')

@section('title', 'Home')

@section('content')
    <section class="page-heading">
        <h1>Home</h1>
        <p>Record stock coming in or going out. Every record updates your manager's financial summary.</p>
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
            <form method="GET" action="{{ route('home') }}" class="search-form" role="search">
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
        </div>

        @error('q')
            <p class="field-error panel-error">{{ $message }}</p>
        @enderror

        @if ($products->isEmpty())
            <div class="empty-state">
                @if ($search !== '')
                    <p class="empty-title">No products match “{{ $search }}”.</p>
                    <p>Try a different name, or <a href="{{ route('home') }}">clear the search</a>.</p>
                @else
                    <p class="empty-title">No products yet.</p>
                    <p>Your manager hasn't added any products.</p>
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
                        <th scope="col" class="numeric">Action</th>
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
                            <td data-label="Action" class="numeric">
                                <button type="button" class="button-outline"
                                    data-dialog-open="record-{{ $product->productID }}">Update stock</button>
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

    @foreach ($products as $product)
        @php
            $failed = (int) old('movementProduct') === $product->productID && $errors->recordMovement->any();
            $type = $failed ? old('skuType', 'outbound') : 'outbound';
            $inbound = $type === 'inbound';
        @endphp
        <dialog id="record-{{ $product->productID }}" class="modal" aria-labelledby="record-title-{{ $product->productID }}"
            @if ($failed) data-open-on-load @endif>
            <form method="POST" action="{{ route('movements.store', $product) }}" class="modal-body"
                data-movement-form>
                @csrf
                <input type="hidden" name="movementProduct" value="{{ $product->productID }}">

                <div class="modal-head">
                    <h2 id="record-title-{{ $product->productID }}">Update stock</h2>
                    <button type="button" class="icon-button" data-dialog-close aria-label="Close">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" aria-hidden="true">
                            <path d="M6 6l12 12M18 6L6 18" />
                        </svg>
                    </button>
                </div>
                <p class="modal-intro">
                    <strong>{{ $product->name }}</strong> · {{ $product->stockQuantity }} in stock
                </p>

                <div class="form-grid">
                    <div class="form-field span-2">
                        <label for="type-{{ $product->productID }}">What happened?</label>
                        <select id="type-{{ $product->productID }}" name="skuType" data-movement-type
                            @class(['input-error' => $failed && $errors->recordMovement->has('skuType')])>
                            <option value="outbound" @selected(! $inbound)>Shipped out / sold (revenue)</option>
                            <option value="inbound" @selected($inbound)>Restocked / purchased (expense)</option>
                        </select>
                        @if ($failed)
                            @error('skuType', 'recordMovement')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>

                    <div class="form-field">
                        <label for="qty-{{ $product->productID }}">Quantity</label>
                        <input id="qty-{{ $product->productID }}" name="skuQuantity" type="number" min="1"
                            max="1000000" step="1" inputmode="numeric" required
                            value="{{ $failed ? old('skuQuantity') : '' }}"
                            @class(['input-error' => $failed && $errors->recordMovement->has('skuQuantity')])>
                        @if ($failed)
                            @error('skuQuantity', 'recordMovement')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>

                    <div class="form-field" data-inbound-only @if (! $inbound) hidden @endif>
                        <label for="price-{{ $product->productID }}">Purchase price (each)</label>
                        <input id="price-{{ $product->productID }}" name="unitPrice" type="number" min="0"
                            step="0.01" inputmode="decimal" data-movement-price
                            value="{{ $failed && old('unitPrice') !== null ? old('unitPrice') : $product->purchasePrice }}"
                            @class(['input-error' => $failed && $errors->recordMovement->has('unitPrice')])>
                        @if ($failed)
                            @error('unitPrice', 'recordMovement')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>

                    <p class="field-hint span-2" data-outbound-only @if ($inbound) hidden @endif>
                        Sold at the selling price: {{ Money::format($product->sellingPrice) }} each.
                    </p>

                    <div class="form-field span-2">
                        <label for="note-{{ $product->productID }}">Note (optional)</label>
                        <input id="note-{{ $product->productID }}" name="skuNote" type="text" maxlength="255"
                            value="{{ $failed ? old('skuNote') : '' }}">
                        @if ($failed)
                            @error('skuNote', 'recordMovement')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="button-secondary" data-dialog-close>Cancel</button>
                    <button type="submit" class="button-primary">Record</button>
                </div>
            </form>
        </dialog>
    @endforeach
@endsection
