@extends('core::layouts.app')

@section('title', 'Danh sách đơn hàng')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold text-gray-800">Danh sách đơn hàng</h1>
        <a href="{{ route('orders.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
            + Tạo đơn mới
        </a>
    </div>

    <div class="bg-white rounded shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-700">#</th>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-700">Tổng tiền</th>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-700">Tiền tệ</th>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-700">Trạng thái</th>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-700">Ngày tạo</th>
                    <th class="text-right px-4 py-3 text-sm font-semibold text-gray-700">Hành động</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm">{{ $order->id }}</td>
                        <td class="px-4 py-3 text-sm font-medium">{{ number_format((float) $order->total, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm">{{ $order->currency }}</td>
                        <td class="px-4 py-3 text-sm">
                            <span class="px-2 py-1 text-xs rounded bg-gray-100">{{ $order->status->value }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-sm text-right">
                            <a href="{{ route('orders.show', $order) }}" class="text-blue-600 hover:underline">Chi tiết</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">Chưa có đơn hàng nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $orders->links() }}</div>
@endsection
