<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChallengeHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $name = $request->header('lang') == 'ar' ? 'name_ar' : 'name_en';
        return [
            'session_id'       => $this->id,
            'subject_id'       => $this->subject_id,
            'lesson_section_id'=> $this->lesson_section_id,
            'section_name'  => optional($this->section)->$name,
            'score'            => $this->score ? (float) $this->score : 0,
            'started_at'       => $this->started_at ? $this->started_at->format('Y-m-d H:i:s') : null,
            'ended_at'         => $this->ended_at ? $this->ended_at->format('Y-m-d H:i:s') : null,
            'duration_minutes' => $this->section?->challenge_duration,
            'total_questions'  => $this->section?->challengeQuestions?->count() ?? 0,
        ];
    }
}
