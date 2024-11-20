@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto bg-white p-6 rounded-lg shadow-lg">
    <h2 class="text-2xl font-bold mb-4">Update File</h2>

    <!-- Display success or error messages -->
    @if (session('success'))
        <div class="bg-green-500 text-white p-2 mb-4">
            {{ session('success') }}
        </div>
    @elseif (session('error'))
        <div class="bg-red-500 text-white p-2 mb-4">
            {{ session('error') }}
        </div>
    @endif

    <!-- Update Form -->
    <form action="{{ route('files.update', ['id' => $file->id]) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="mb-4">
            <label for="file" class="block text-sm font-medium text-gray-700">Select New File</label>
            <input type="file" id="file" name="file" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.txt,.xlsm" class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="w-full bg-blue-500 text-white p-2 rounded-lg mt-4 hover:bg-yellow-500">Update File</button>
    </form>

    <!-- Back Button -->
    <a href="{{ route('uploads.list') }}" class="text-blue-500 hover:underline mt-4 block">Back to File List</a>
</div>
@endsection
