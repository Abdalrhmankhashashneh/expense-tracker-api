<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BalanceTransactionResource extends JsonResource
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
            'type' => $this->type,
            'amount' => (float) $this->amount,
            'source' => $this->source,
            'source_label' => $this->source_label,
            'description' => $this->description,
            'balance_after' => (float) $this->balance_after,
            'expense_id' => $this->expense_id,
            'expense' => $this->whenLoaded('expense', function () {
                return new ExpenseResource($this->expense);
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
