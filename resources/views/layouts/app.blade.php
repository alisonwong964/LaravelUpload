<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Laravel Application')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" crossorigin="anonymous">
</head>
<body class="bg-gray-100">

    <header class="bg-gray-200 text-black p-4">
        <h1 class="text-2xl font-bold">Laravel PHP File Upload System</h1>
    </header>

    <div class="container mx-auto mt-4">
        @yield('content')
    </div>

</body>
</html>
