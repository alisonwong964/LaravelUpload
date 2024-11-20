@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto bg-white p-6 rounded-lg shadow-lg overflow-hidden">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold">List of Uploaded Files</h2>
        <a href="{{ route('uploads.home') }}" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-yellow-600">Upload File</a>
    </div>

    <!-- Success or Error Messages -->
    @if (session('success'))
        <div id="success-message" class="bg-green-500 text-white p-2 mb-4">
            {{ session('success') }}
        </div>
    @elseif (session('error'))
        <div class="bg-red-500 text-white p-2 mb-4">
            {{ session('error') }}
        </div>
    @endif

    <!-- Success message auto-hide -->
    <script>
        setTimeout(function() {
            var message = document.getElementById('success-message');
            if (message) {
                message.style.display = 'none';
            }
        }, 3000);
    </script>

    <table class="min-w-full bg-white border border-gray-200 rounded-lg">
        <thead>
            <tr class="bg-gray-100 text-gray-600">
                <th class="py-3 px-4 border-b">No</th>
                <th class="py-3 px-4 border-b">Filename</th>
                <th class="py-3 px-4 border-b">Size</th>
                <th class="py-3 px-4 border-b">Uploaded At</th>
                <th class="py-3 px-4 border-b">Read</th>
                <th class="py-3 px-4 border-b">Update</th>
                <th class="py-3 px-4 border-b">Delete</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($files as $index => $file)
                <tr>
                    <td class="py-3 px-4 border-b">{{ $index + 1 }}</td>
                    <td class="py-3 px-4 border-b">{{ $file->name }}</td>
                    <td class="py-3 px-4 border-b">{{ $file->size }} bytes</td>
                    <td class="py-3 px-4 border-b">{{ $file->uploaded_at->format('Y-m-d H:i:s') }}</td>
                    <td class="py-3 px-4 border-b">
                        <form action="{{ route('files.view', ['file_name' => $file->name]) }}" method="GET">
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Read</button>
                        </form>


                    </td>
                    <td class="py-3 px-4 border-b">
                        <a href="{{ route('uploads.update', $file->id) }}" class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">Update</a>
                    </td>
                    <td class="py-3 px-4 border-b">
                        <form action="{{ route('files.destroy', $file->id) }}" method="POST" class="delete-form">
                            @csrf
                            @method('DELETE')
                            <button type="button" class="delete-button bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600" data-id="{{ $file->id }}">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var deleteButtons = document.querySelectorAll('.delete-button');

        deleteButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                deleteFile(button);
            });
        });
    });

    function deleteFile(button) {
        if (confirm('Are you sure you want to delete this file?')) {
            var form = button.closest('form');
            form.submit();
        }
    }
</script>
@endsection
