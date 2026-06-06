<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\DTO\Auth\TokenIssuedData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property TokenIssuedData $resource
 */
final class TokenResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->resource->token,
            'user' => UserResource::make($this->resource->user),
        ];
    }
}
