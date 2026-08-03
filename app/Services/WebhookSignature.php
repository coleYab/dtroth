<?php

namespace App\Services;

/**
 * Signs and verifies request payloads using an HMAC-SHA256 shared secret.
 * Used for outbound flow callbacks and inbound webhook validation so both
 * sides of the platform agree on the exact same signature scheme.
 */
class WebhookSignature
{
    /**
     * Build the "sha256=..." signature header value for a raw payload.
     */
    public function sign(string $payload, string $secret): string
    {
        return 'sha256='.hash_hmac('sha256', $payload, $secret);
    }

    /**
     * Whether the provided signature matches the payload under the secret.
     */
    public function verify(string $payload, string $secret, string $signature): bool
    {
        $expected = $this->sign($payload, $secret);

        return hash_equals($expected, $signature);
    }
}
