<fieldset class="space-y-3 rounded-lg border border-stone-200 p-4" data-row>
    @if ($ingredients->isNotEmpty())
        <label class="block">Ingredient
            <select class="{{ $inputClass }}" name="ingredients[{{ $index }}][id]" data-name="ingredients[__index__][id]">
                <option value="">Choose an ingredient</option>
                @foreach ($ingredients as $ingredient)
                    @php($selectedId = data_get($row, 'id'))
                    <option value="{{ $ingredient->id }}" @selected(is_scalar($selectedId) && (string) $selectedId === (string) $ingredient->id)>{{ $ingredient->name }}</option>
                @endforeach
            </select>
        </label>
    @endif
    <details @if ($ingredients->isEmpty() || data_get($row, 'new_name')) open @endif>
        <summary class="cursor-pointer text-sm font-semibold text-orange-700">Add a new ingredient</summary>
        <label class="mt-2 block text-sm">New ingredient name
            <input class="{{ $inputClass }}" maxlength="80" name="ingredients[{{ $index }}][new_name]" data-name="ingredients[__index__][new_name]" data-new-ingredient-name value="{{ is_scalar(data_get($row, 'new_name')) ? data_get($row, 'new_name') : '' }}">
        </label>
        @if ($ingredients->isNotEmpty())
            <p class="mt-1 text-sm text-stone-600">Leave the ingredient dropdown blank when entering a new name.</p>
        @endif
    </details>
    <div class="grid gap-3 sm:grid-cols-2">
        <label>Quantity
            <input class="{{ $inputClass }}" type="number" min="0.01" max="999999.99" step="0.01" name="ingredients[{{ $index }}][quantity]" data-name="ingredients[__index__][quantity]" value="{{ is_scalar(data_get($row, 'quantity')) ? data_get($row, 'quantity') : '' }}">
        </label>
        <label>Unit
            <input class="{{ $inputClass }}" list="ingredient-units" maxlength="50" name="ingredients[{{ $index }}][unit]" data-name="ingredients[__index__][unit]" value="{{ is_scalar(data_get($row, 'unit')) ? data_get($row, 'unit') : '' }}">
        </label>
    </div>
    <label class="block">Notes
        <input class="{{ $inputClass }}" maxlength="255" name="ingredients[{{ $index }}][notes]" data-name="ingredients[__index__][notes]" value="{{ is_scalar(data_get($row, 'notes')) ? data_get($row, 'notes') : '' }}">
    </label>
    <div class="flex flex-wrap items-center justify-between gap-2">
        <label class="flex items-center gap-2">
            <input type="checkbox" value="1" name="ingredients[{{ $index }}][is_main]" data-name="ingredients[__index__][is_main]" @checked(data_get($row, 'is_main'))>
            Main ingredient
        </label>
        <button class="text-sm font-semibold text-red-700" type="button" data-remove>Remove ingredient</button>
    </div>
</fieldset>
