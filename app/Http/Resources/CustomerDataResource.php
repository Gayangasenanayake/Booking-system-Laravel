<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerDataResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'schedule_count' => $this->resource->schedule_count,
            'bookings_total' => $this->resource->bookings_total,
            'data' => parent::toArray($request)
        ];
    }
}
