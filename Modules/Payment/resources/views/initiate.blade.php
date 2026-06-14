@extends('core::layouts.app')

@section('title', 'Thanh toán đơn #'.$order->id)

@section('content')
    <h1 class="text-2xl font-bold text-gray-800 mb-4">Thanh toán đơn #{{ $order->id }}</h1>

    <div class="bg-white rounded shadow p-6 max-w-2xl">
        <div class="mb-6 pb-4 border-b">
            <div class="text-sm text-gray-500">Số tiền cần thanh toán</div>
            <div class="text-2xl font-bold text-gray-800">{{ number_format((float) $order->total, 0, ',', '.') }} {{ $order->currency }}</div>
        </div>

        <form method="POST" action="{{ route('payments.initiate', $order) }}" class="space-y-4">
            @csrf

            <fieldset>
                <legend class="text-sm font-medium text-gray-700 mb-2">Chọn phương thức thanh toán:</legend>
                <div class="space-y-2">
                    @foreach ($methods as $method)
                        <label class="flex items-center p-3 border rounded hover:bg-gray-50 cursor-pointer">
                            <input
                                type="radio"
                                name="method"
                                value="{{ $method->value }}"
                                required
                                class="mr-3">
                            <span class="font-medium">{{ $method->label() }}</span>
                        </label>
                    @endforeach
                </div>
                @error('method')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </fieldset>

            <div class="flex items-center gap-2 pt-4 border-t">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                    Thanh toán
                </button>
                <a href="{{ route('orders.show', $order) }}" class="px-4 py-2 text-gray-600 hover:text-gray-900">Huỷ</a>
            </div>
        </form>
    </div>
@endsection
