@props(['field', 'label', 'options', 'selected' => [], 'parents' => null])

<div data-search-picker>
    <label class="mb-2 block text-sm font-medium" hidden data-picker-search-label>
        Search {{ strtolower($label) }}
        <input class="mt-1 block w-full rounded-lg border border-stone-300 px-3 py-2" type="search" autocomplete="off" data-picker-search>
    </label>
    <ul class="max-h-48 overflow-y-auto rounded-lg border border-stone-200" aria-label="{{ $label }}">
        @foreach ($options as $id => $name)
            <li data-picker-option="{{ $name }}">
                <label class="block cursor-pointer">
                    <input class="peer sr-only" type="checkbox" name="{{ $field }}[]" value="{{ $id }}"
                        @if ($parents !== null) data-category-parent="{{ $parents[$id] ?? '' }}" @endif
                        @checked(in_array($id, $selected))>
                    <span class="block border-b border-stone-100 px-3 py-2 text-sm hover:bg-stone-50 peer-checked:bg-orange-50 peer-checked:text-orange-800 peer-indeterminate:bg-stone-100 peer-focus-visible:ring-2 peer-focus-visible:ring-inset peer-focus-visible:ring-orange-700">{{ $name }}</span>
                </label>
            </li>
        @endforeach
    </ul>
    <p class="mt-2 text-sm text-stone-600" role="status" hidden data-picker-empty>No matching options.</p>
    <div class="mt-3 flex flex-wrap gap-2" aria-label="Selected {{ strtolower($label) }}" data-picker-selected>
        <template>
            <button type="button" class="rounded-md bg-orange-50 px-3 py-1.5 text-sm font-semibold text-orange-800 hover:bg-orange-100">
                <span data-picker-chip-label></span> <span aria-hidden="true">×</span>
            </button>
        </template>
    </div>
</div>
