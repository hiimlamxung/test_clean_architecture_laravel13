<aside x-show="sidebarOpen" x-transition class="w-64 bg-gray-900 text-gray-100 flex flex-col">
    <div class="p-4 border-b border-gray-800">
        <h1 class="text-xl font-bold">{{ config('app.name') }}</h1>
        <p class="text-xs text-gray-400 mt-1">ERP System</p>
    </div>
    <nav class="flex-1 overflow-y-auto py-4">
        <a href="{{ route('dashboard') }}" class="block px-4 py-2 hover:bg-gray-800 {{ request()->routeIs('dashboard') ? 'bg-gray-800' : '' }}">
            Dashboard
        </a>
        <a href="{{ route('orders.index') }}" class="block px-4 py-2 hover:bg-gray-800 {{ request()->routeIs('orders.*') ? 'bg-gray-800' : '' }}">
            Đơn hàng
        </a>
    </nav>
</aside>
