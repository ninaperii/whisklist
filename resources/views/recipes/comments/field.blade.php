<div>
    <label class="block text-sm font-semibold" for="comment-body">Comment</label>
    <textarea class="mt-2 block w-full rounded-lg border border-stone-300 px-3 py-2" id="comment-body" name="body" rows="4" maxlength="2000" required>{{ is_string(old('body')) ? old('body') : $commentBody }}</textarea>
    @error('body')
        <p class="mt-2 text-sm text-red-700" role="alert">{{ $message }}</p>
    @enderror
</div>
