@extends('core::layouts.app')

@section('title', 'Tạo đơn hàng')

@section('content')
    <h1 class="text-2xl font-bold text-gray-800 mb-4">Tạo đơn hàng mới</h1>

    <div class="bg-white rounded shadow p-6 max-w-2xl">
        <form method="POST" action="{{ route('orders.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="total" class="block text-sm font-medium text-gray-700">Tổng tiền</label>
                <input
                    type="number"
                    id="total"
                    name="total"
                    value="{{ old('total') }}"
                    min="1"
                    step="0.01"
                    required
                    class="mt-1 w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('total')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="currency" class="block text-sm font-medium text-gray-700">Tiền tệ (3 ký tự)</label>
                <input
                    type="text"
                    id="currency"
                    name="currency"
                    value="{{ old('currency', 'VND') }}"
                    maxlength="3"
                    required
                    class="mt-1 w-full border border-gray-300 rounded px-3 py-2 uppercase focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('currency')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                    Tạo đơn
                </button>
                <a href="{{ route('orders.index') }}" class="px-4 py-2 text-gray-600 hover:text-gray-900">Huỷ</a>
            </div>
        </form>
    </div>
@endsection
