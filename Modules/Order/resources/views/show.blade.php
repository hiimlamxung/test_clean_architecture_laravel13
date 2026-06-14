@extends('core::layouts.app')

@section('title', 'Đơn hàng #'.$order->id)

@section('content')
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold text-gray-800">Đơn hàng #{{ $order->id }}</h1>
        <a href="{{ route('orders.index') }}" class="text-blue-600 hover:underline">← Danh sách</a>
    </div>

    <div class="bg-white rounded shadow p-6 max-w-2xl">
        <dl class="grid grid-cols-2 gap-4">
            <div>
                <dt class="text-sm text-gray-500">Tổng tiền</dt>
                <dd class="text-lg font-semibold">{{ number_format((float) $order->total, 0, ',', '.') }} {{ $order->currency }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Trạng thái</dt>
                <dd><span class="px-2 py-1 text-xs rounded bg-gray-100">{{ $order->status->value }}</span></dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Ngày tạo</dt>
                <dd>{{ $order->created_at?->format('d/m/Y H:i') }}</dd>
            </div>
        </dl>

        @if ($order->status->value === 'Pending')
            <div class="mt-6 pt-6 border-t">
                <a href="{{ route('payments.initiate.form', $order) }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                    Thanh toán đơn hàng
                </a>
            </div>
        @endif
    </div>
@endsection
