<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'دواء')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>

<body class="min-h-screen bg-gradient-to-b from-sky-100 via-sky-50 to-white flex items-center justify-center">
    <main class="w-full max-w-md p-6">
        @yield('content')
    </main>
    @stack('scripts')
</body>

</html>
