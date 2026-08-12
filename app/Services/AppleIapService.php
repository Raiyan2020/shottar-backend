<?php

namespace App\Services;

use App\Models\IosBundleProduct;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AppleIapService
{
    public const PRODUCTION_API = 'https://api.storekit.itunes.apple.com';
    public const SANDBOX_API = 'https://api.storekit-sandbox.itunes.apple.com';
    public const PRODUCTION_VERIFY = 'https://buy.itunes.apple.com/verifyReceipt';
    public const SANDBOX_VERIFY = 'https://sandbox.itunes.apple.com/verifyReceipt';

    public function __construct(protected AppleJwt $jwt)
    {
    }

    /**
     * Verify an Apple purchase and grant subjects. Idempotent on transaction_id.
     *
     * @return array{success: bool, order_id?: int, subject_ids?: array, already_granted?: bool, message?: string}
     */
    public function verifyAndGrant(User $user, string $productId, string $receipt, ?string $transactionId, string $source = 'purchase'): array
    {
        try {
            $verified = $this->verifyWithApple($receipt, $transactionId);
        } catch (\Throwable $e) {
            Log::warning('Apple IAP verification failed', [
                'user_id' => $user->id,
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'تعذر التحقق من عملية الشراء',
            ];
        }

        $bundleId = (string) config('services.apple.bundle_id', 'com.raiyansoft.shottar');
        if (($verified['bundle_id'] ?? null) !== $bundleId) {
            return [
                'success' => false,
                'message' => 'تعذر التحقق من عملية الشراء',
            ];
        }

        $verifiedProductId = (string) ($verified['product_id'] ?? '');
        if ($verifiedProductId === '' || $verifiedProductId !== $productId) {
            return [
                'success' => false,
                'message' => 'تعذر التحقق من عملية الشراء',
            ];
        }

        $txId = (string) ($verified['transaction_id'] ?? $transactionId ?? '');
        if ($txId === '') {
            return [
                'success' => false,
                'message' => 'تعذر التحقق من عملية الشراء',
            ];
        }

        $originalTxId = (string) ($verified['original_transaction_id'] ?? $txId);

        return DB::transaction(function () use ($user, $verifiedProductId, $txId, $originalTxId, $verified, $source) {
            $existing = $this->findExistingOrder($txId, $originalTxId, $user->id);
            if ($existing) {
                return [
                    'success' => true,
                    'order_id' => $existing->id,
                    'subject_ids' => $existing->items()->pluck('subject_id')->values()->all(),
                    'already_granted' => true,
                ];
            }

            $subjects = $this->subjectsForProduct($verifiedProductId);
            if ($subjects->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'تعذر التحقق من عملية الشراء',
                ];
            }

            try {
                $order = $this->grantOrder($user, $subjects, [
                    'apple_transaction_id' => $txId,
                    'apple_original_transaction_id' => $originalTxId,
                    'apple_product_id' => $verifiedProductId,
                    'apple_environment' => $verified['environment'] ?? null,
                    'is_all_materials' => IosBundleProduct::where('ios_product_id', $verifiedProductId)->exists(),
                    'source' => $source,
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                $existing = $this->findExistingOrder($txId, $originalTxId, $user->id);
                if ($existing) {
                    return [
                        'success' => true,
                        'order_id' => $existing->id,
                        'subject_ids' => $existing->items()->pluck('subject_id')->values()->all(),
                        'already_granted' => true,
                    ];
                }
                throw $e;
            }

            return [
                'success' => true,
                'order_id' => $order->id,
                'subject_ids' => $subjects->pluck('id')->values()->all(),
                'already_granted' => false,
            ];
        });
    }

    public function handleNotification(string $signedPayload): void
    {
        $decoded = $this->jwt->decodeJws($signedPayload, true);
        $payload = $decoded['payload'];
        $type = $payload['notificationType'] ?? $payload['notification_type'] ?? null;
        $data = $payload['data'] ?? [];

        $signedTransaction = $data['signedTransactionInfo'] ?? null;
        $transaction = is_string($signedTransaction)
            ? ($this->jwt->decodeJws($signedTransaction, true)['payload'] ?? [])
            : [];

        $txId = (string) ($transaction['transactionId'] ?? $transaction['originalTransactionId'] ?? '');
        $originalTxId = (string) ($transaction['originalTransactionId'] ?? $txId);

        if (in_array($type, ['REFUND', 'REVOKE'], true)) {
            $this->revokeAccess($txId, $originalTxId);
            return;
        }

        if ($type === 'CONSUMPTION_REQUEST' && $txId !== '') {
            $this->sendConsumptionResponse($txId, $data['environment'] ?? 'Production');
        }
    }

    public function subjectsForProduct(string $productId): Collection
    {
        $subject = Subject::where('ios_product_id', $productId)->first();
        if ($subject) {
            return collect([$subject]);
        }

        $bundle = IosBundleProduct::where('ios_product_id', $productId)->first();
        if (! $bundle) {
            return collect();
        }

        return Subject::query()
            ->where('status', 1)
            ->where('grade_id', $bundle->grade_id)
            ->where(function ($q) use ($bundle) {
                $q->where('semester_id', $bundle->semester_id)
                    ->orWhereHas('semesters', function ($sq) use ($bundle) {
                        $sq->where('semesters.id', $bundle->semester_id);
                    });
            })
            ->get();
    }

    /**
     * @return array{bundle_id: string, product_id: string, transaction_id: string, original_transaction_id: string, environment: string, is_bundle?: bool}
     */
    protected function verifyWithApple(string $receipt, ?string $transactionId): array
    {
        $receipt = trim($receipt);

        if ($transactionId && $this->hasServerApiCredentials()) {
            try {
                return $this->verifyViaServerApi($transactionId);
            } catch (\Throwable $e) {
                Log::info('Apple Server API lookup failed, falling back', ['error' => $e->getMessage()]);
            }
        }

        if ($this->looksLikeJws($receipt)) {
            if ($this->hasServerApiCredentials()) {
                $payload = $this->jwt->decodeJws($receipt, true)['payload'];
                $jwsTxId = (string) ($payload['transactionId'] ?? $transactionId ?? '');
                if ($jwsTxId !== '') {
                    return $this->verifyViaServerApi($jwsTxId);
                }
            }

            $payload = $this->jwt->decodeJws($receipt, false)['payload'];

            return $this->mapJwsPayload($payload);
        }

        return $this->verifyViaReceipt($receipt, $transactionId);
    }

    protected function verifyViaServerApi(string $transactionId): array
    {
        $token = $this->jwt->makeToken();
        $path = '/inApps/v1/transactions/'.$transactionId;

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(20)
            ->get(self::PRODUCTION_API.$path);

        $environment = 'Production';
        if ($response->status() === 404 || $response->status() === 401) {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(20)
                ->get(self::SANDBOX_API.$path);
            $environment = 'Sandbox';
        }

        if (! $response->successful()) {
            throw new RuntimeException('Apple Server API error: '.$response->status());
        }

        $signed = $response->json('signedTransactionInfo');
        if (! is_string($signed) || $signed === '') {
            throw new RuntimeException('Apple Server API returned no transaction.');
        }

        $payload = $this->jwt->decodeJws($signed, false)['payload'];
        $mapped = $this->mapJwsPayload($payload);
        $mapped['environment'] = $payload['environment'] ?? $environment;

        return $mapped;
    }

    protected function verifyViaReceipt(string $receipt, ?string $transactionId): array
    {
        $password = (string) config('services.apple.shared_secret');
        $body = [
            'receipt-data' => $receipt,
            'exclude-old-transactions' => true,
        ];
        if ($password !== '') {
            $body['password'] = $password;
        }

        $response = Http::timeout(20)->post(self::PRODUCTION_VERIFY, $body);
        $json = $response->json() ?? [];
        $environment = 'Production';

        if ((int) ($json['status'] ?? 0) === 21007) {
            $response = Http::timeout(20)->post(self::SANDBOX_VERIFY, $body);
            $json = $response->json() ?? [];
            $environment = 'Sandbox';
        }

        if ((int) ($json['status'] ?? -1) !== 0) {
            throw new RuntimeException('verifyReceipt status '.($json['status'] ?? 'unknown'));
        }

        $bundleId = $json['receipt']['bundle_id'] ?? $json['receipt']['bid'] ?? null;
        $inApp = collect($json['latest_receipt_info'] ?? $json['receipt']['in_app'] ?? []);

        $match = $inApp->first(function ($item) use ($transactionId) {
            if (! $transactionId) {
                return true;
            }

            return (string) ($item['transaction_id'] ?? '') === $transactionId
                || (string) ($item['original_transaction_id'] ?? '') === $transactionId;
        }) ?: $inApp->last();

        if (! $match) {
            throw new RuntimeException('No matching in-app transaction in receipt.');
        }

        return [
            'bundle_id' => (string) $bundleId,
            'product_id' => (string) ($match['product_id'] ?? ''),
            'transaction_id' => (string) ($match['transaction_id'] ?? ''),
            'original_transaction_id' => (string) ($match['original_transaction_id'] ?? $match['transaction_id'] ?? ''),
            'environment' => $environment,
        ];
    }

    protected function mapJwsPayload(array $payload): array
    {
        $productId = (string) ($payload['productId'] ?? '');

        return [
            'bundle_id' => (string) ($payload['bundleId'] ?? ''),
            'product_id' => $productId,
            'transaction_id' => (string) ($payload['transactionId'] ?? ''),
            'original_transaction_id' => (string) ($payload['originalTransactionId'] ?? $payload['transactionId'] ?? ''),
            'environment' => (string) ($payload['environment'] ?? 'Production'),
            'is_bundle' => IosBundleProduct::where('ios_product_id', $productId)->exists(),
        ];
    }

    protected function findExistingOrder(string $txId, string $originalTxId, int $userId): ?Order
    {
        return Order::query()
            ->where(function ($q) use ($txId, $originalTxId, $userId) {
                $q->where('apple_transaction_id', $txId)
                    ->orWhere(function ($q2) use ($originalTxId, $userId) {
                        $q2->where('apple_original_transaction_id', $originalTxId)
                            ->where('user_id', $userId)
                            ->where('status', 'paid');
                    });
            })
            ->lockForUpdate()
            ->first();
    }

    protected function grantOrder(User $user, Collection $subjects, array $apple): Order
    {
        $subjects->loadMissing('grade');

        $paymentMethodId = PaymentMethod::where('slug', 'apple_iap')->value('id');
        $isBundle = (bool) ($apple['is_all_materials'] ?? false);

        $order = Order::create([
            'user_id' => $user->id,
            'total' => $isBundle
                ? (optional($subjects->first()?->grade)->all_materials_price ?? $subjects->sum('price'))
                : $subjects->sum('price'),
            'status' => 'paid',
            'payment_method_id' => $paymentMethodId,
            'payment_reference' => $apple['apple_transaction_id'],
            'apple_transaction_id' => $apple['apple_transaction_id'],
            'apple_original_transaction_id' => $apple['apple_original_transaction_id'],
            'apple_product_id' => $apple['apple_product_id'],
            'apple_environment' => $apple['apple_environment'],
            'is_all_materials' => $isBundle,
        ]);

        $order->items()->createMany(
            $subjects->map(fn (Subject $subject) => [
                'subject_id' => $subject->id,
                'price' => $subject->price ?? 0,
            ])->all()
        );

        return $order;
    }

    protected function revokeAccess(string $txId, string $originalTxId): void
    {
        $query = Order::query()->where('status', 'paid');
        $query->where(function ($q) use ($txId, $originalTxId) {
            if ($txId !== '') {
                $q->where('apple_transaction_id', $txId);
            }
            if ($originalTxId !== '') {
                $q->orWhere('apple_original_transaction_id', $originalTxId);
            }
        });

        $query->update(['status' => 'cancelled']);
    }

    protected function sendConsumptionResponse(string $transactionId, string $environment): void
    {
        if (! $this->hasServerApiCredentials()) {
            return;
        }

        $base = strcasecmp($environment, 'Sandbox') === 0 ? self::SANDBOX_API : self::PRODUCTION_API;

        try {
            Http::withToken($this->jwt->makeToken())
                ->acceptJson()
                ->timeout(20)
                ->put($base.'/inApps/v1/transactions/consumption/'.$transactionId, [
                    'accountTenure' => 0,
                    'appAccountToken' => '',
                    'consumptionStatus' => 3,
                    'customerConsented' => true,
                    'deliveryStatus' => 0,
                    'lifetimeDollarsPurchased' => 0,
                    'lifetimeDollarsRefunded' => 0,
                    'platform' => 1,
                    'playTime' => 0,
                    'refundPreference' => 2,
                    'sampleContentProvided' => true,
                    'userStatus' => 1,
                ]);
        } catch (\Throwable $e) {
            Log::warning('Apple consumption response failed', ['error' => $e->getMessage()]);
        }
    }

    protected function hasServerApiCredentials(): bool
    {
        return (string) config('services.apple.key_id') !== ''
            && (string) config('services.apple.issuer_id') !== ''
            && ((string) config('services.apple.private_key') !== ''
                || (string) config('services.apple.private_key_path') !== '');
    }

    protected function looksLikeJws(string $value): bool
    {
        return substr_count($value, '.') === 2 && ! str_starts_with($value, 'MII');
    }
}
