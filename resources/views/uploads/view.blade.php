{{-- resources/views/view.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Document</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-3xl mx-auto bg-white p-6 rounded-lg shadow-lg">
        <h2 class="text-2xl font-bold mb-4">Document Viewer</h2>

        @if (!empty($error))
            <div class="text-red-500">
                {{ $error }}
            </div>
        @elseif (!empty($content))
            <div>{!! $content !!}</div>
        @else
            <div class="text-red-500">
                The requested file could not be found or is not supported for inline viewing.
            </div>
        @endif

        <a href="{{ route('uploads.list') }}" class="text-blue-500 hover:underline mt-4 block">Back to File List</a>
    </div>
</body>
</html>
