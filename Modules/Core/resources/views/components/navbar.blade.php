<header class="bg-white border-b shadow-sm">
    <div class="flex items-center justify-between px-6 py-3">
        <button @click="sidebarOpen = !sidebarOpen" class="text-gray-600 hover:text-gray-900">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        <div class="flex items-center gap-4" x-data="{ open: false }">
            <span class="text-sm text-gray-700">{{ auth()->user()->name ?? '' }}</span>
            <div class="relative">
                <button @click="open = !open" class="flex items-center gap-2 text-sm text-gray-700 hover:text-gray-900">
                    <span class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center font-semibold">
                        {{ mb_substr(auth()->user()->name ?? '?', 0, 1) }}
                    </span>
                </button>
                <div x-show="open" @click.outside="open = false" x-transition class="absolute right-0 mt-2 w-44 bg-white border rounded shadow-lg z-10">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-2 hover:bg-gray-100 text-sm">
                            Đăng xuất
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
