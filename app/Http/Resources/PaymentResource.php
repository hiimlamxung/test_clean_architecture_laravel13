<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Payment
 */
final class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $payload = $this->gateway_payload ?? [];

        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'method' => $this->method->value,
            'status' => $this->status->value,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'gateway_reference' => $this->gateway_reference,
            'redirect_url' => $payload['redirect_url'] ?? null,
            'qr_data' => $payload['qr_data'] ?? null,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
