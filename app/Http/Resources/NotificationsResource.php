<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lang = $request->header('lang', 'ar');

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'order_id' => $this->order_id,
            'type' => (string) ($this->type ?? 'general'),
            'title' => $lang === 'en'
                ? ($this->title_en ?: $this->title)
                : ($this->title ?: $this->title_en),
            'body' => $lang === 'en'
                ? ($this->body_en ?: $this->body)
                : ($this->body ?: $this->body_en),
            'data' => $this->data,
            'is_read' => (bool) $this->is_read,
            'created_at' => optional($this->created_at)->diffForHumans(),
        ];
    }
}
