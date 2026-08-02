<?php

namespace Database\Seeders;

use App\Enums\ActivityEventType;
use App\Enums\AgeQueryType;
use App\Enums\EnvironmentName;
use App\Models\ActivityEvent;
use App\Models\AgeQueryPreset;
use App\Models\ApiCall;
use App\Models\ApiKey;
use App\Models\App;
use App\Models\AuditLog;
use App\Models\Consent;
use App\Models\Invoice;
use App\Models\SandboxIdentity;
use App\Models\StatusCheck;
use App\Models\StatusIncident;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Verification;
use App\Models\VerificationFlow;
use App\Models\VerificationSetting;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Models\WebhookEvent;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Demo Developer',
            'email' => 'dev@example.com',
            'password' => 'password',
        ]);

        $presets = collect([
            ['type' => AgeQueryType::EqualAbove, 'value' => 13, 'label' => '13+', 'description' => 'Must be at least 13 years old.', 'sort_order' => 0],
            ['type' => AgeQueryType::EqualAbove, 'value' => 16, 'label' => '16+', 'description' => 'Must be at least 16 years old.', 'sort_order' => 1],
            ['type' => AgeQueryType::EqualAbove, 'value' => 18, 'label' => '18+', 'description' => 'Must be at least 18 years old.', 'sort_order' => 2],
            ['type' => AgeQueryType::EqualAbove, 'value' => 21, 'label' => '21+', 'description' => 'Must be at least 21 years old.', 'sort_order' => 3],
            ['type' => AgeQueryType::EqualBelow, 'value' => 65, 'label' => '65−', 'description' => 'Must be 65 years old or younger.', 'sort_order' => 4],
        ])->map(fn (array $preset) => AgeQueryPreset::create($preset));

        $webhookEvents = collect([
            ['name' => WebhookEvent::VERIFICATION_COMPLETED, 'label' => 'Verification completed'],
            ['name' => WebhookEvent::VERIFICATION_EXPIRED, 'label' => 'Verification expired'],
            ['name' => WebhookEvent::CONSENT_REVOKED, 'label' => 'Consent revoked'],
        ])->map(fn (array $event) => WebhookEvent::create($event));

        Subscription::factory()->create([
            'user_id' => $user->id,
            'plan' => 'pro',
            'status' => Subscription::STATUS_ACTIVE,
            'renews_at' => now()->addDays(18),
            'channel' => 'card',
        ]);

        Invoice::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        $apps = collect();

        $apps->push(App::factory()->create([
            'user_id' => $user->id,
            'name' => 'Fayda Checkout',
            'redirect_uris' => ['https://checkout.faydademo.app/callback'],
            'allowed_origins' => ['https://checkout.faydademo.app'],
        ]));

        $apps->push(App::factory()->create([
            'user_id' => $user->id,
            'name' => 'Age Gate Mobile',
            'redirect_uris' => [
                'https://agegate.app/oauth/callback',
                'com.agegate.app://oauth/callback',
            ],
            'allowed_origins' => ['https://agegate.app'],
        ]));

        $apps->push(App::factory()->disabled()->create([
            'user_id' => $user->id,
            'name' => 'Legacy KYC Portal',
            'redirect_uris' => ['https://kyc.legacy.internal/callback'],
            'allowed_origins' => ['https://kyc.legacy.internal'],
        ]));

        foreach ($apps as $app) {
            $app->environments()->create([
                'name' => EnvironmentName::Sandbox,
            ]);

            $app->environments()->create([
                'name' => EnvironmentName::Production,
                'is_live' => true,
            ]);

            $keys = collect();

            $production = $app->environments->firstWhere('name', EnvironmentName::Production);
            $sandbox = $app->environments->firstWhere('name', EnvironmentName::Sandbox);

            $keys->push(ApiKey::factory()->forEnvironment($production)->create([
                'app_id' => $app->id,
                'name' => 'Production key',
                'last_used_at' => now()->subMinutes(12),
            ]));

            $keys->push(ApiKey::factory()->forEnvironment($sandbox)->expiringSoon()->create([
                'app_id' => $app->id,
                'name' => 'Sandbox key',
            ]));

            $keys->push(ApiKey::factory()->forEnvironment($sandbox)->revoked()->create([
                'app_id' => $app->id,
                'name' => 'Legacy key',
            ]));

            ApiCall::factory()->count(120)->create(['app_id' => $app->id]);
            Verification::factory()->count(35)->create(['app_id' => $app->id]);
            Consent::factory()->count(15)->withEvents()->create(['app_id' => $app->id]);

            VerificationSetting::create([
                'app_id' => $app->id,
                'consent_text' => VerificationSetting::DEFAULT_CONSENT_TEXT,
                're_verify_months' => 6,
            ]);

            VerificationFlow::create([
                'app_id' => $app->id,
                'brand_color' => '#0f172a',
                'success_redirect_url' => 'https://example.com/success',
                'failure_redirect_url' => 'https://example.com/failure',
            ]);

            $app->ageQueryPresets()->attach($presets->take(3)->pluck('id'));

            $webhook = Webhook::factory()->create([
                'app_id' => $app->id,
                'url' => 'https://example.com/webhooks/age-check',
                'description' => 'Age verification results',
            ]);

            $webhook->events()->attach($webhookEvents->pluck('id'));

            WebhookDelivery::factory()->count(8)->create([
                'webhook_id' => $webhook->id,
                'created_at' => now()->subDays(fake()->numberBetween(0, 12)),
            ]);

            WebhookDelivery::factory()->failed()->count(2)->create([
                'webhook_id' => $webhook->id,
                'created_at' => now()->subHours(fake()->numberBetween(2, 18)),
            ]);

            $today = now();

            for ($day = 0; $day < 10; $day++) {
                $date = $today->copy()->subDays($day)->setTime(fake()->numberBetween(8, 18), fake()->numberBetween(0, 59));

                ActivityEvent::factory()->ofType(ActivityEventType::KeyRotation)->create([
                    'app_id' => $app->id,
                    'description' => "API key for {$app->name} rotated",
                    'created_at' => $date,
                ]);

                ActivityEvent::factory()->ofType(ActivityEventType::ScopeChange)->create([
                    'app_id' => $app->id,
                    'description' => 'Age verification scopes updated for '.$app->name,
                    'created_at' => $date->copy()->subHours(3),
                ]);

                ActivityEvent::factory()->ofType(ActivityEventType::FailedVerification)->create([
                    'app_id' => $app->id,
                    'description' => 'Verification failed: document did not match',
                    'created_at' => $date->copy()->subHours(6),
                ]);

                AuditLog::factory()->forApp($app)->ofAction(AuditLog::ACTION_KEY_CREATED)->create([
                    'user_id' => $user->id,
                    'description' => "API key created for {$app->name}",
                    'created_at' => $date,
                ]);
            }

            AuditLog::factory()->forApp($app)->ofAction(AuditLog::ACTION_PRESET_CHANGED)->create([
                'user_id' => $user->id,
                'description' => 'Age-query presets updated for '.$app->name,
                'created_at' => now()->subDay(),
            ]);
        }

        foreach ([
            ['key' => 'fayda-test-a', 'label' => 'Test User A', 'age' => 20],
            ['key' => 'fayda-test-b', 'label' => 'Test User B', 'age' => 16],
            ['key' => 'fayda-test-c', 'label' => 'Test User C', 'age' => 25],
            ['key' => 'fayda-test-d', 'label' => 'Test User D', 'age' => 65],
        ] as $identity) {
            SandboxIdentity::create($identity);
        }

        foreach (config('status.components', []) as $component) {
            for ($day = 0; $day < 30; $day++) {
                $date = now()->subDays($day)->setTime(fake()->numberBetween(0, 23), fake()->numberBetween(0, 59));

                StatusCheck::factory()
                    ->forComponent($component['key'])
                    ->ok()
                    ->on($date)
                    ->create();

                if (fake()->boolean(8)) {
                    StatusCheck::factory()
                        ->forComponent($component['key'])
                        ->degraded()
                        ->on($date->copy()->addHours(1))
                        ->create();
                }
            }
        }

        StatusIncident::factory()->resolved()->create([
            'title' => 'Elevated latency on the Verification API',
            'component' => 'verification-api',
        ]);

        StatusIncident::factory()->resolved()->create([
            'title' => 'Webhook delivery delays',
            'component' => 'webhook-delivery',
        ]);
    }
}
