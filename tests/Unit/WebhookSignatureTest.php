<?php

use App\Services\WebhookSignature;

test('sign produces a sha256 HMAC signature', function () {
    $signature = new WebhookSignature;

    $expected = 'sha256='.hash_hmac('sha256', '{"result":true}', 'secret');

    expect($signature->sign('{"result":true}', 'secret'))->toBe($expected);
});

test('signatures are secret-specific', function () {
    $signature = new WebhookSignature;

    expect($signature->sign('payload', 'secret-a'))->not->toBe($signature->sign('payload', 'secret-b'));
});

test('verify accepts a genuine signature and rejects tampered payloads', function () {
    $signature = new WebhookSignature;

    $signed = $signature->sign('{"result":true}', 'secret');

    expect($signature->verify('{"result":true}', 'secret', $signed))->toBeTrue()
        ->and($signature->verify('{"result":false}', 'secret', $signed))->toBeFalse()
        ->and($signature->verify('{"result":true}', 'other-secret', $signed))->toBeFalse()
        ->and($signature->verify('{"result":true}', 'secret', 'sha256=deadbeef'))->toBeFalse();
});

test('sign treats JSON encoding options consistently', function () {
    $signature = new WebhookSignature;

    $payload = ['url' => 'https://example.com/a/b', 'name' => 'Zebras 🦓'];

    $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    expect($signature->sign((string) $body, 'secret'))
        ->toBe('sha256='.hash_hmac('sha256', $body, 'secret'));
});
