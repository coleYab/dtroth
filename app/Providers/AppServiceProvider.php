<?php

namespace App\Providers;

use App\Models\ApiKey;
use App\Models\App;
use App\Models\Observers\ApiKeyObserver;
use App\Models\Observers\AppObserver;
use App\Models\Observers\WebhookObserver;
use App\Models\Webhook;
use App\Providers\Identity\MockFaydaProvider;
use App\Providers\Identity\SandboxIdentityProvider;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(MockFaydaProvider::class);
        $this->app->singleton(SandboxIdentityProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(
            (int) config('verify.api_requests_per_minute', 60),
        )->by($request->user()?->id ?: $request->ip())->response(function (Request $request, array $headers) {
            return response()->json([
                'error' => [
                    'code' => 'too_many_requests',
                    'message' => 'Too many requests. Please retry later.',
                ],
            ], 429, $headers);
        }));

        App::observe(AppObserver::class);
        ApiKey::observe(ApiKeyObserver::class);
        Webhook::observe(WebhookObserver::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
