<?php

namespace App\Services;

use RuntimeException;

class AppleJwt
{
    public function makeToken(): string
    {
        $keyId = (string) config('services.apple.key_id');
        $issuerId = (string) config('services.apple.issuer_id');
        $bundleId = (string) config('services.apple.bundle_id');
        $privateKey = $this->privateKey();

        if ($keyId === '' || $issuerId === '' || $privateKey === '') {
            throw new RuntimeException('Apple App Store Server API credentials are not configured.');
        }

        $now = time();
        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'ES256',
            'kid' => $keyId,
            'typ' => 'JWT',
        ], JSON_UNESCAPED_SLASHES));
        $payload = $this->base64UrlEncode(json_encode([
            'iss' => $issuerId,
            'iat' => $now,
            'exp' => $now + 1200,
            'aud' => 'appstoreconnect-v1',
            'bid' => $bundleId,
        ], JSON_UNESCAPED_SLASHES));

        $data = $header.'.'.$payload;
        $key = openssl_pkey_get_private($privateKey);
        if ($key === false) {
            throw new RuntimeException('Invalid Apple private key.');
        }

        $ok = openssl_sign($data, $derSignature, $key, OPENSSL_ALGO_SHA256);
        if (! $ok) {
            throw new RuntimeException('Failed to sign Apple JWT.');
        }

        return $data.'.'.$this->base64UrlEncode($this->derToJose($derSignature));
    }

    /**
     * Decode a JWS compact serialization. Optionally verify using the x5c leaf certificate.
     *
     * @return array{header: array, payload: array}
     */
    public function decodeJws(string $jws, bool $verifySignature = true): array
    {
        $parts = explode('.', $jws);
        if (count($parts) !== 3) {
            throw new RuntimeException('Invalid JWS.');
        }

        [$headerB64, $payloadB64, $sigB64] = $parts;
        $header = json_decode($this->base64UrlDecode($headerB64), true);
        $payload = json_decode($this->base64UrlDecode($payloadB64), true);

        if (! is_array($header) || ! is_array($payload)) {
            throw new RuntimeException('Invalid JWS payload.');
        }

        if ($verifySignature) {
            $this->verifyEs256($headerB64.'.'.$payloadB64, $this->base64UrlDecode($sigB64), $header);
        }

        return ['header' => $header, 'payload' => $payload];
    }

    protected function verifyEs256(string $data, string $joseSignature, array $header): void
    {
        $x5c = $header['x5c'][0] ?? null;
        if (! is_string($x5c) || $x5c === '') {
            throw new RuntimeException('JWS is missing x5c certificate.');
        }

        $pem = "-----BEGIN CERTIFICATE-----\n".chunk_split($x5c, 64, "\n").'-----END CERTIFICATE-----';
        $publicKey = openssl_pkey_get_public($pem);
        if ($publicKey === false) {
            throw new RuntimeException('Unable to read JWS certificate.');
        }

        $der = $this->joseToDer($joseSignature);
        $ok = openssl_verify($data, $der, $publicKey, OPENSSL_ALGO_SHA256);
        if ($ok !== 1) {
            throw new RuntimeException('JWS signature verification failed.');
        }
    }

    protected function privateKey(): string
    {
        $raw = (string) config('services.apple.private_key');
        if ($raw !== '') {
            return str_contains($raw, 'BEGIN')
                ? $raw
                : "-----BEGIN PRIVATE KEY-----\n".chunk_split(str_replace(['\n', ' '], '', $raw), 64, "\n").'-----END PRIVATE KEY-----';
        }

        $path = (string) config('services.apple.private_key_path');
        if ($path === '') {
            return '';
        }

        if (! str_starts_with($path, '/') && ! preg_match('/^[A-Za-z]:\\\\/', $path)) {
            $path = storage_path($path);
        }

        if (! is_file($path)) {
            throw new RuntimeException('Apple private key file not found.');
        }

        return (string) file_get_contents($path);
    }

    protected function derToJose(string $der): string
    {
        $offset = 0;
        if (ord($der[$offset++]) !== 0x30) {
            throw new RuntimeException('Invalid ECDSA signature.');
        }

        $seqLen = ord($der[$offset++]);
        if ($seqLen & 0x80) {
            $nbytes = $seqLen & 0x7F;
            $offset += $nbytes;
        }

        if (ord($der[$offset++]) !== 0x02) {
            throw new RuntimeException('Invalid ECDSA signature.');
        }
        $rLen = ord($der[$offset++]);
        $r = substr($der, $offset, $rLen);
        $offset += $rLen;

        if (ord($der[$offset++]) !== 0x02) {
            throw new RuntimeException('Invalid ECDSA signature.');
        }
        $sLen = ord($der[$offset++]);
        $s = substr($der, $offset, $sLen);

        $r = str_pad(ltrim($r, "\x00"), 32, "\x00", STR_PAD_LEFT);
        $s = str_pad(ltrim($s, "\x00"), 32, "\x00", STR_PAD_LEFT);

        return $r.$s;
    }

    protected function joseToDer(string $jose): string
    {
        if (strlen($jose) !== 64) {
            throw new RuntimeException('Invalid ES256 signature length.');
        }

        $r = ltrim(substr($jose, 0, 32), "\x00");
        $s = ltrim(substr($jose, 32, 32), "\x00");
        if (ord($r[0]) > 0x7F) {
            $r = "\x00".$r;
        }
        if (ord($s[0]) > 0x7F) {
            $s = "\x00".$s;
        }

        return "\x30".chr(4 + strlen($r) + strlen($s))."\x02".chr(strlen($r)).$r."\x02".chr(strlen($s)).$s;
    }

    public function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($data, '-_', '+/')) ?: '';
    }
}
