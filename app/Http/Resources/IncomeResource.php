<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncomeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'monthly_amount' => number_format($this->monthly_amount, 2, '.', ''),
            'effective_from' => $this->effective_from,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
