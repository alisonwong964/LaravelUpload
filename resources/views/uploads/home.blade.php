<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script>
        const checkStatusUrl = "{{ route('file.checkStatus') }}";
        const moveFileUrl = "{{ route('file.move') }}";
        const uploadUrl = "{{ route('uploadDB') }}";
        const skipUrl = "{{ route('skip') }}";
    </script>

    <title>Laravel PHP File Upload System</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" crossorigin="anonymous">
    {{-- <link rel="stylesheet" href="{{ mix('css/style.css') }}"> --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    {{-- <script src="{{ mix('js/formHandler.js') }}" defer></script> --}}
</head>
<body class="bg-gray-100 p-6">
    <div class="text-3xl font-bold mx-4 mb-10">
        <h1>Laravel PHP File Upload System</h1>
    </div>
    <div class="max-w-md mx-auto bg-white p-6 rounded-lg shadow-lg">
        <h2 class="text-2xl font-bold mb-4">Upload Your Files</h2>

        {{-- <form id="uploadForm" novalidate enctype="multipart/form-data"> --}}
        <form id="uploadForm" action="{{ route('file.upload') }}" method="POST" enctype="multipart/form-data">
            @csrf <!-- Laravel CSRF token -->
            <input type="hidden" id="csrf_token" value="{{ csrf_token() }}">

            <label for="file" class="block mb-2">Choose files to upload:</label>

            <div id="drop-area" class="border border-gray-300 rounded p-4 mb-4 w-full text-center">
                <p>Drag & drop files here or click to select files</p>
                <!-- Removed hidden attribute and added hidden-input class for CSS control -->
                <input type="file" name="file[]" id="file-input" accept=".pdf, .doc, .docx, .jpg, .png, .jpeg, .xlsm, .txt" multiple class="hidden-input" hidden>
                <div id="preview-container"></div>
            </div>

            <!-- Container for error messages -->
            <div id="error-messages" class="error-container" style="color: red;"></div>
            <button id="uploadButton" type="submit" class="w-full bg-blue-500 text-white p-2 rounded-lg hover:bg-yellow-500">Upload</button>
        </form>

        {{-- <script src="{{ mix('js/uploadBox.js') }}" defer></script> --}}

        <button id="uploadNewFileBtn" class="w-full bg-green-500 text-white p-2 rounded-lg hover:bg-green-700 mt-4">
            Upload New File
        </button>
        <div class="max-w-md mx-auto flex justify-center items-center mb-4">
            <a href="{{ route('uploads.list') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-yellow-600 mt-4">View Files</a>
        </div>
    </div>
    <h3 class="text-xl font-bold mb-4">File Upload Status</h3>
    <table id="fileStatusTable" class="table-auto w-full border-collapse border border-gray-400">
        <thead>
            <tr>
                <th class="border border-gray-300 px-4 py-2">Task ID</th>
                <th class="border border-gray-300 px-4 py-2">File Name</th>
                <th class="border border-gray-300 px-4 py-2">Status</th>
                <th class="border border-gray-300 px-4 py-2">Upload Time</th>
                <th class="border border-gray-300 px-4 py-2">Result</th>
                <th class="border border-gray-300 px-4 py-2">Actions</th>
            </tr>
        </thead>
        <tbody>
            <!-- Rows will be added dynamically -->
        </tbody>
    </table>
</body>
</html>
