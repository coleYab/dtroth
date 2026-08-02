<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ChapaService
{
    /**
     * The base URL for the Chapa API.
     */
    private const BASE_URL = 'https://api.chapa.co/v1';

    /**
     * Whether the service is configured with a secret key.
     */
    public function isConfigured(): bool
    {
        return filled(config('services.chapa.secret'));
    }

    /**
     * Initialize a transaction and return the checkout URL.
     *
     * @param  array<string, mixed>  $payload
     */
    public function initialize(array $payload): Response
    {
        return $this->request()->post(self::BASE_URL.'/transaction/initialize', $payload);
    }

    /**
     * Verify a transaction by reference.
     */
    public function verify(string $txRef): Response
    {
        return $this->request()->get(self::BASE_URL.'/transaction/verify/'.$txRef);
    }

    /**
     * Build the authenticated client for Chapa requests.
     */
    private function request(): PendingRequest
    {
        return Http::withToken(config('services.chapa.secret'))
            ->acceptJson()
            ->asJson()
            ->timeout(15);
    }
}
