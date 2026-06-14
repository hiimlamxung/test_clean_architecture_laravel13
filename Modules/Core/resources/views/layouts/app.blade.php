<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ERP') - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100" x-data="{ sidebarOpen: true }">
    <div class="flex h-screen">
        @include('core::components.sidebar')
        <div class="flex-1 flex flex-col overflow-hidden">
            @include('core::components.navbar')
            <main class="flex-1 overflow-y-auto p-6">
                @include('core::components.alert')
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
