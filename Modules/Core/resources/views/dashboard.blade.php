@extends('core::layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="bg-white rounded shadow p-6">
        <h1 class="text-3xl font-bold text-gray-800">Xin chào, {{ auth()->user()->name }}!</h1>
        <p class="mt-2 text-gray-600">Dashboard ERP đang được phát triển.</p>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('orders.index') }}" class="block p-4 border rounded hover:bg-gray-50">
                <div class="text-lg font-semibold text-gray-800">Đơn hàng</div>
                <div class="text-sm text-gray-500 mt-1">Xem và quản lý đơn hàng</div>
            </a>
        </div>
    </div>
@endsection
