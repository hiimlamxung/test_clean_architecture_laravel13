@extends('core::layouts.app')

@section('title', 'Payment #'.$payment->id)

@section('content')
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold text-gray-800">Thanh toán #{{ $payment->id }}</h1>
        <a href="{{ route('orders.show', $payment->order_id) }}" class="text-blue-600 hover:underline">← Đơn hàng</a>
    </div>

    <div class="bg-white rounded shadow p-6 max-w-2xl">
        <dl class="grid grid-cols-2 gap-4">
            <div>
                <dt class="text-sm text-gray-500">Phương thức</dt>
                <dd class="font-medium">{{ $payment->method->label() }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Trạng thái</dt>
                <dd>
                    @php
                        $colors = [
                            'Succeeded' => 'bg-green-100 text-green-800',
                            'Failed' => 'bg-red-100 text-red-800',
                            'Cancelled' => 'bg-gray-200 text-gray-800',
                            'Processing' => 'bg-yellow-100 text-yellow-800',
                            'Pending' => 'bg-blue-100 text-blue-800',
                        ];
                        $color = $colors[$payment->status->value] ?? 'bg-gray-100';
                    @endphp
                    <span class="px-2 py-1 text-xs rounded {{ $color }}">{{ $payment->status->value }}</span>
                </dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Số tiền</dt>
                <dd class="text-lg font-semibold">{{ number_format((float) $payment->amount, 0, ',', '.') }} {{ $payment->currency }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Gateway reference</dt>
                <dd class="font-mono text-sm">{{ $payment->gateway_reference ?? '—' }}</dd>
            </div>
            @if ($payment->paid_at)
                <div>
                    <dt class="text-sm text-gray-500">Đã thanh toán lúc</dt>
                    <dd>{{ $payment->paid_at->format('d/m/Y H:i') }}</dd>
                </div>
            @endif
        </dl>

        @php $redirectUrl = $payment->gateway_payload['redirect_url'] ?? null; @endphp
        @if ($redirectUrl && ! $payment->status->isFinal())
            <div class="mt-6 pt-6 border-t">
                <a href="{{ $redirectUrl }}" target="_blank" rel="noopener" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                    Mở trang gateway
                </a>
            </div>
        @endif
    </div>
@endsection
