@extends('layouts.manager')

@section('title', 'Categories')

@section('content')
    @php
        $addFailed = $errors->addCategory->any();
    @endphp

    <section class="page-heading">
        <h1>Categories</h1>
        <p>Group products so reports and lists are easier to read.</p>
    </section>

    <section class="panel">
        <div class="toolbar toolbar-end">
            <button type="button" class="button-primary add-button" data-dialog-open="add-category-dialog">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
                    stroke-width="2.2" stroke-linecap="round" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                <span>Add category</span>
            </button>
        </div>

        @if ($categories->isEmpty())
            <div class="empty-state">
                <p class="empty-title">No categories yet.</p>
                <p>Add one, then pick it when you add or edit a product.</p>
            </div>
        @else
            <table class="product-table">
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Description</th>
                        <th scope="col" class="numeric">Products</th>
                        <th scope="col" class="numeric">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $category)
                        <tr>
                            <td data-label="Name" class="product-name">{{ $category->name }}</td>
                            <td data-label="Description">{{ $category->description ?: '—' }}</td>
                            <td data-label="Products" class="numeric">{{ $category->products_count }}</td>
                            <td data-label="Actions" class="numeric">
                                <div class="row-actions">
                                    <button type="button" class="button-outline"
                                        data-dialog-open="edit-category-{{ $category->categoryID }}">Edit</button>
                                    <button type="button" class="button-danger"
                                        data-dialog-open="delete-category-{{ $category->categoryID }}">Delete</button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

    <dialog id="add-category-dialog" class="modal" aria-labelledby="add-category-title"
        @if ($addFailed) data-open-on-load @endif>
        <form method="POST" action="{{ route('categories.store') }}" class="modal-body">
            @csrf
            <div class="modal-head">
                <h2 id="add-category-title">Add category</h2>
                <button type="button" class="icon-button" data-dialog-close aria-label="Close">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" /></svg>
                </button>
            </div>

            <div class="form-grid">
                <div class="form-field span-2">
                    <label for="add-category-name">Name</label>
                    <input id="add-category-name" name="name" type="text" maxlength="255" required
                        value="{{ $addFailed ? old('name') : '' }}" @class(['input-error' => $errors->addCategory->has('name')])>
                    @if ($errors->addCategory->has('name'))
                        <p class="field-error">{{ $errors->addCategory->first('name') }}</p>
                    @endif
                </div>
                <div class="form-field span-2">
                    <label for="add-category-description">Description (optional)</label>
                    <input id="add-category-description" name="description" type="text" maxlength="1000"
                        value="{{ $addFailed ? old('description') : '' }}">
                    @if ($errors->addCategory->has('description'))
                        <p class="field-error">{{ $errors->addCategory->first('description') }}</p>
                    @endif
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="button-secondary" data-dialog-close>Cancel</button>
                <button type="submit" class="button-primary">Save category</button>
            </div>
        </form>
    </dialog>

    @foreach ($categories as $category)
        @php
            $editFailed = (int) old('editCategoryID') === $category->categoryID && $errors->editCategory->any();
        @endphp

        <dialog id="edit-category-{{ $category->categoryID }}" class="modal"
            aria-labelledby="edit-category-title-{{ $category->categoryID }}" @if ($editFailed) data-open-on-load @endif>
            <form method="POST" action="{{ route('categories.update', $category) }}" class="modal-body">
                @csrf
                @method('PUT')
                <input type="hidden" name="editCategoryID" value="{{ $category->categoryID }}">
                <div class="modal-head">
                    <h2 id="edit-category-title-{{ $category->categoryID }}">Edit category</h2>
                    <button type="button" class="icon-button" data-dialog-close aria-label="Close">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" /></svg>
                    </button>
                </div>

                <div class="form-grid">
                    <div class="form-field span-2">
                        <label for="edit-category-name-{{ $category->categoryID }}">Name</label>
                        <input id="edit-category-name-{{ $category->categoryID }}" name="name" type="text"
                            maxlength="255" required value="{{ $editFailed ? old('name') : $category->name }}"
                            @class(['input-error' => $editFailed && $errors->editCategory->has('name')])>
                        @if ($editFailed && $errors->editCategory->has('name'))
                            <p class="field-error">{{ $errors->editCategory->first('name') }}</p>
                        @endif
                    </div>
                    <div class="form-field span-2">
                        <label for="edit-category-description-{{ $category->categoryID }}">Description (optional)</label>
                        <input id="edit-category-description-{{ $category->categoryID }}" name="description" type="text"
                            maxlength="1000" value="{{ $editFailed ? old('description') : $category->description }}">
                        @if ($editFailed && $errors->editCategory->has('description'))
                            <p class="field-error">{{ $errors->editCategory->first('description') }}</p>
                        @endif
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="button-secondary" data-dialog-close>Cancel</button>
                    <button type="submit" class="button-primary">Save changes</button>
                </div>
            </form>
        </dialog>

        <dialog id="delete-category-{{ $category->categoryID }}" class="modal"
            aria-labelledby="delete-category-title-{{ $category->categoryID }}">
            <form method="POST" action="{{ route('categories.destroy', $category) }}" class="modal-body">
                @csrf
                @method('DELETE')
                <div class="modal-head">
                    <h2 id="delete-category-title-{{ $category->categoryID }}">Delete category</h2>
                    <button type="button" class="icon-button" data-dialog-close aria-label="Close">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" /></svg>
                    </button>
                </div>
                <p class="modal-intro">
                    Delete <strong>{{ $category->name }}</strong>? Its {{ $category->products_count }} product(s)
                    stay in your inventory and simply become uncategorised.
                </p>

                <div class="modal-actions">
                    <button type="button" class="button-secondary" data-dialog-close>Cancel</button>
                    <button type="submit" class="button-danger button-danger-solid">Delete category</button>
                </div>
            </form>
        </dialog>
    @endforeach
@endsection
