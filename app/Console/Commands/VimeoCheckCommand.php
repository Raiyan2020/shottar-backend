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
    protected $signature = 'vimeo:check
                            {--upload-ticket : Create and immediately delete a tiny Vimeo TUS upload ticket}';

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
            $client = $vimeoService->getClient();
            $response = $client->request('/me');

            if (($response['status'] ?? 500) >= 400) {
                $this->error('❌ Vimeo rejected /me: HTTP '.($response['status'] ?? 'unknown'));

                return self::FAILURE;
            }


            $this->info('✅ Vimeo Access Token Information:');
            $this->line('User: ' . ($response['body']['name'] ?? 'Unknown'));
            $this->line('Link: ' . ($response['body']['link'] ?? 'N/A'));
            $this->line('Account plan: ' . ($response['body']['account'] ?? 'Unknown'));

            $quota = $response['body']['upload_quota'] ?? [];
            if (is_array($quota)) {
                $free = data_get($quota, 'space.free');
                $max = data_get($quota, 'space.max');

                if (is_numeric($free) && is_numeric($max)) {
                    $this->line('Storage free: '.number_format(((float) $free) / 1024 / 1024, 2).' MB');
                    $this->line('Storage limit: '.number_format(((float) $max) / 1024 / 1024, 2).' MB');
                }
            }

            // /me لا يعرض صلاحيات التوكن. Endpoint التحقق من OAuth هو المصدر
            // الصحيح، ويرجع scope كـ string مفصول بمسافات.
            $verify = $client->request('/oauth/verify');
            $rawScopes = $verify['body']['scope'] ?? '';
            $scopes = is_array($rawScopes)
                ? $rawScopes
                : preg_split('/[\s,]+/', trim((string) $rawScopes), -1, PREG_SPLIT_NO_EMPTY);

            $this->info("\n🔑 Scopes available:");
            foreach ($scopes as $scope) {
                $this->line("- " . $scope);
            }

            if (! in_array('upload', $scopes, true)) {
                $this->error("\n⚠️ Token does NOT have 'upload' permission! يجب توليد توكين جديد فيه الصلاحية.");

                return self::FAILURE;
            }

            $this->info("\n✅ Token has upload permission.");

            if ($this->option('upload-ticket')) {
                return $this->checkUploadTicket($client);
            }

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error('❌ Failed to check Vimeo token: ' . $e->getMessage());

            return self::FAILURE;
        }
    }

    private function checkUploadTicket(\Vimeo\Vimeo $client): int
    {
        $this->newLine();
        $this->info('Testing POST /me/videos (TUS)...');

        $response = $client->request('/me/videos', [
            'upload' => [
                'approach' => 'tus',
                'size' => 1,
            ],
            'name' => 'Shottar API connectivity test',
        ], 'POST', [
            'Accept' => 'application/vnd.vimeo.*+json;version=3.4',
        ]);

        $status = (int) ($response['status'] ?? 500);
        $body = is_array($response['body'] ?? null) ? $response['body'] : [];
        $uri = $body['uri'] ?? null;
        $hasUploadLink = filled($body['upload']['upload_link'] ?? null);

        if ($status >= 200 && $status < 300 && $uri && $hasUploadLink) {
            $this->info('✅ Vimeo created a valid TUS upload ticket.');

            // Remove the empty placeholder created only for this connectivity test.
            $delete = $client->request($uri, [], 'DELETE');
            $deleteStatus = (int) ($delete['status'] ?? 500);

            if ($deleteStatus >= 200 && $deleteStatus < 300) {
                $this->line('✅ Test placeholder deleted.');
            } else {
                $this->warn('⚠️ Ticket worked, but the empty test placeholder could not be deleted (HTTP '.$deleteStatus.').');
            }

            return self::SUCCESS;
        }

        $this->error('❌ Vimeo rejected the upload ticket: HTTP '.$status);
        $this->line('Error: '.($body['error'] ?? 'Unknown Vimeo error'));
        $this->line('Developer message: '.($body['developer_message'] ?? 'N/A'));
        $this->line('Error code: '.($body['error_code'] ?? 'N/A'));

        foreach (($body['invalid_parameters'] ?? []) as $parameter) {
            $this->line('- '.json_encode($parameter, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        return self::FAILURE;
    }
}
