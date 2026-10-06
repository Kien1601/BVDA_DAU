@if (session('success'))
    <div class="mb-4 rounded border border-green-200 bg-green-50 p-3 text-green-800">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="mb-4 rounded border border-red-200 bg-red-50 p-3 text-red-800">
        {{ session('error') }}
    </div>
@endif