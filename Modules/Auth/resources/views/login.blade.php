@extends('core::layouts.guest')

@section('title', 'Đăng nhập')

@section('content')
    <div class="text-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Đăng nhập hệ thống</h2>
        <p class="text-sm text-gray-500 mt-1">{{ config('app.name') }}</p>
    </div>

    @include('core::components.alert')

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                class="mt-1 w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Mật khẩu</label>
            <input
                type="password"
                id="password"
                name="password"
                required
                class="mt-1 w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center text-sm text-gray-600">
            <input type="checkbox" name="remember" class="mr-2 rounded border-gray-300">
            Nhớ đăng nhập
        </label>

        <button
            type="submit"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded transition">
            Đăng nhập
        </button>
    </form>
@endsection
