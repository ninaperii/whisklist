<div class="rounded-lg border border-stone-200 p-4" data-row>
    <label class="block"><span data-step-label>Step {{ is_numeric($index) ? $loop->iteration : '' }}</span>
        <textarea class="{{ $inputClass }}" name="steps[{{ $index }}]" data-name="steps[__index__]" rows="3" maxlength="5000" required>{{ is_scalar($instruction) ? $instruction : '' }}</textarea>
    </label>
    <button class="mt-2 text-sm font-semibold text-red-700" type="button" data-remove>Remove step</button>
</div>
