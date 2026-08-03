<?php

namespace App\Console\Commands;

use App\Enums\ComponentStatus;
use App\Enums\EnvironmentName;
use App\Models\StatusCheck;
use App\Services\IdentityProviderRegistry;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

#[Signature('status:check')]
#[Description('Ping each configured component and record the result in status_checks')]
class CheckComponents extends Command
{
    public function __construct(private readonly IdentityProviderRegistry $registry)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        foreach (config('status.components', []) as $component) {
            $started = hrtime(true);

            try {
                $response = Http::timeout(config('status.timeout_ms') / 1000)
                    ->get(url($component['url']));

                $responseTimeMs = (int) ((hrtime(true) - $started) / 1_000_000);
                $degraded = $responseTimeMs > config('status.degraded_above_ms');

                $status = $response->status() === $component['expected_status']
                    ? ($degraded ? ComponentStatus::Degraded : ComponentStatus::Ok)
                    : ComponentStatus::Down;

                $responseTimeMs = $response->status() === $component['expected_status']
                    ? $responseTimeMs
                    : null;
            } catch (\Throwable) {
                $status = ComponentStatus::Down;
                $responseTimeMs = null;
            }

            StatusCheck::create([
                'component' => $component['key'],
                'status' => $status,
                'response_time_ms' => $responseTimeMs,
                'checked_at' => now(),
            ]);

            $this->line(sprintf('[%s] %s', $component['key'], $status->value));
        }

        $this->checkIdentityProvider();

        return self::SUCCESS;
    }

    /**
     * Probe the configured identity provider and record its availability.
     */
    private function checkIdentityProvider(): void
    {
        try {
            $provider = $this->registry->resolve(EnvironmentName::Production);
            $status = $provider->isAvailable() ? ComponentStatus::Ok : ComponentStatus::Down;
        } catch (\Throwable) {
            $status = ComponentStatus::Down;
        }

        StatusCheck::create([
            'component' => 'identity_provider',
            'status' => $status,
            'response_time_ms' => null,
            'checked_at' => now(),
        ]);

        $this->line(sprintf('[identity_provider] %s', $status->value));
    }
}
