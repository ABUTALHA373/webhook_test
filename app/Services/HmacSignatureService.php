<?php

namespace App\Services;

class HmacSignatureService
{
    /**
     * Compute HMAC signature for a payload.
     *
     * @param  string  $algorithm  sha256, sha1, sha512, md5
     * @param  string  $format  hex, base64, prefix_hex, stripe
     */
    public function generate(string $payload, string $secret, string $algorithm = 'sha256', string $format = 'hex'): string
    {
        $algorithm = strtolower($algorithm);
        if (! in_array($algorithm, hash_hmac_algos(), true)) {
            $algorithm = 'sha256';
        }

        if ($format === 'stripe') {
            $timestamp = time();
            $signedPayload = $timestamp.'.'.$payload;
            $hash = hash_hmac($algorithm, $signedPayload, $secret);

            return "t={$timestamp},v1={$hash}";
        }

        if ($format === 'base64') {
            $rawHash = hash_hmac($algorithm, $payload, $secret, true);

            return base64_encode($rawHash);
        }

        $hex = hash_hmac($algorithm, $payload, $secret);

        if ($format === 'prefix_hex') {
            return "{$algorithm}={$hex}";
        }

        return $hex;
    }

    /**
     * Build the header array for an HMAC signature.
     *
     * @return array<string, string>
     */
    public function buildHeader(
        string $payload,
        ?string $secret,
        string $headerName = 'X-Signature-256',
        string $algorithm = 'sha256',
        string $format = 'hex'
    ): array {
        if (! $secret) {
            return [];
        }

        $signature = $this->generate($payload, $secret, $algorithm, $format);

        return [$headerName => $signature];
    }
}
