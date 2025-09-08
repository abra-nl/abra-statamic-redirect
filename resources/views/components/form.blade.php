<form method="POST" action="{{ $action }}">
    @csrf
    @if($method == 'PATCH')
        @method('PATCH')
    @endif

    <div class="mb-4">
        <label id="source-label" aria-label="Source (required)" for="source" class="block mb-1">Source URL <span class="text-red-500">*</span></label>
        <input id="source" type="text" aria-describedby="source-hint" name="source" value="{{ old('source', $redirect['source'] ?? '') }}" class="input-text" required>
        <div id="source-hint" class="text-xs text-gray-600 mt-1">
            <p>The URL path to redirect from. Do not include the domain.</p>
            <p>You can use wildcards like <code>/blog/*</code> to match multiple paths.</p>
        </div>
        @error('source')
        <p id="source-error" role="alert" class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="mb-4">
        <label id="destination-label" aria-label="Destination (required)" for="destination" class="block mb-1">Destination URL <span class="text-red-500">*</span></label>
        <input id="destination" aria-describedby="destination-hint" type="text" name="destination" value="{{ old('destination', $redirect['destination'] ?? '') }}" class="input-text" required>
        <p id="destination-hint" class="text-xs text-gray-600 mt-1">The URL to redirect to. Can be a full URL or a relative path.</p>
        @error('destination')
        <p id="destination-error" role="alert" class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="mb-4">
        <label id="status-code-label" aria-label="Status code (required)" for="status-code" class="block mb-1">Status Code <span class="text-red-500">*</span></label>
        <select id="status-code" name="status_code" class="input-text" required>
            @foreach($statusCodes as $code => $label)
                <option value="{{ $code }}" {{ old('status_code', $redirect['status_code'] ?? 301) == $code ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('status_code')
        <p id="status-code-error" role="alert" class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2h-1V9a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm leading-5 font-medium text-blue-800">Wildcard Examples</h3>
                <div class="mt-2 text-sm leading-5 text-blue-700">
                    <p>Source: <code>/blog/*</code> → Destination: <code>/articles/article-name</code></p>
                    <p>This will redirect <code>/blog/my-post</code> to <code>/articles/article-name</code></p>
                </div>
            </div>
        </div>
    </div>

    <div class="flex justify-end mt-4">
        <a href="{{ cp_route('abra-redirects.index') }}" class="btn">Cancel</a>
        <button type="submit" class="btn-primary ml-2">{{ $submitButtonText }}</button>
    </div>
</form>
<?php
