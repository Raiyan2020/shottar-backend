<?php

namespace App\Jobs;

use App\Helpers\Functions;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BroadcastWawpImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use Functions;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        public int $userId,
        public string $imageUrl,
        public string $caption = ''
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);
        if (!$user || empty($user->phone)) return;

        // ارسال
        $this->whatsappImage($user->phone, $this->imageUrl, $this->caption);

        // تهدئة بسيطة لتجنب ضغط/حظر (حوالي 0.2 ثانية)
        usleep(200000);
    }
}
