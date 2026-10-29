@php
    $err = fn (string $field) => $errors->getBag($bag)->first($field);
@endphp

<div class="form-grid">
    <div class="form-field span-2">
        <label for="{{ $prefix }}-name">Product name</label>
        <input id="{{ $prefix }}-name" name="name" type="text" value="{{ $values['name'] }}" maxlength="255" required
            @class(['input-error' => $err('name')])>
        @if ($err('name'))
            <p class="field-error">{{ $err('name') }}</p>
        @endif
    </div>

    <div class="form-field span-2">
        <label for="{{ $prefix }}-category">Category (optional)</label>
        <select id="{{ $prefix }}-category" name="categoryID" @class(['input-error' => $err('categoryID')])>
            <option value="">No category</option>
            @foreach ($categories as $category)
                <option value="{{ $category->categoryID }}" @selected((string) $values['categoryID'] === (string) $category->categoryID)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
        @if ($err('categoryID'))
            <p class="field-error">{{ $err('categoryID') }}</p>
        @endif
    </div>

    <div class="form-field">
        <label for="{{ $prefix }}-selling">Selling price</label>
        <input id="{{ $prefix }}-selling" name="sellingPrice" type="number" min="0" step="0.01" inputmode="decimal"
            value="{{ $values['sellingPrice'] }}" required @class(['input-error' => $err('sellingPrice')])>
        @if ($err('sellingPrice'))
            <p class="field-error">{{ $err('sellingPrice') }}</p>
        @endif
    </div>

    <div class="form-field">
        <label for="{{ $prefix }}-min">Minimum stock level</label>
        <input id="{{ $prefix }}-min" name="minStockLevel" type="number" min="0" step="1" inputmode="numeric"
            value="{{ $values['minStockLevel'] }}" required @class(['input-error' => $err('minStockLevel')])>
        @if ($err('minStockLevel'))
            <p class="field-error">{{ $err('minStockLevel') }}</p>
        @endif
    </div>
</div>
