<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\VimeoService;

class VimeoCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vimeo:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Vimeo access token scopes and user info';

    /**
     * Execute the console command.
     */
    public function handle(VimeoService $vimeoService)
    {
        try {
            $response = $vimeoService->getClient()->request('/me');


            $this->info('✅ Vimeo Access Token Information:');
            $this->line('User: ' . ($response['body']['name'] ?? 'Unknown'));
            $this->line('Link: ' . ($response['body']['link'] ?? 'N/A'));

            $scopes = $response['body']['scopes'] ?? [];
            $this->info("\n🔑 Scopes available:");
            foreach ($scopes as $scope) {
                $this->line("- " . $scope);
            }

            if (!in_array('upload', $scopes)) {
                $this->error("\n⚠️ Token does NOT have 'upload' permission! يجب توليد توكين جديد فيه الصلاحية.");
            }

        } catch (\Exception $e) {
            $this->error('❌ Failed to check Vimeo token: ' . $e->getMessage());
        }
    }
}
