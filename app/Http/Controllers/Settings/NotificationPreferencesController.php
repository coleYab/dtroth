<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class NotificationPreferencesController extends Controller
{
    /**
     * All notification types with their preference keys and metadata.
     *
     * @return array<int, array{key: string, title: string, description: string}>
     */
    public static function types(): array
    {
        return [
            [
                'key' => 'webhook_failing',
                'title' => 'Webhook failures',
                'description' => 'Email the team when a webhook has failed deliveries in the last 24 hours.',
            ],
            [
                'key' => 'key_expiring',
                'title' => 'API key expiry',
                'description' => 'Email the team when an API key expires within the next 7 days.',
            ],
            [
                'key' => 'usage_threshold',
                'title' => 'Usage thresholds',
                'description' => 'Email the team when app usage crosses 80% and 100% of the plan quota.',
            ],
            [
                'key' => 'invoice_failed',
                'title' => 'Failed payments',
                'description' => 'Email the account owner when an invoice payment fails.',
            ],
        ];
    }

    /**
     * Show the user's notification preferences page.
     */
    public function edit(Request $request): Response
    {
        $preferences = $request->user()->notification_preferences ?? [];

        return Inertia::render('settings/notifications', [
            'types' => collect(self::types())
                ->map(fn (array $type) => [
                    'key' => $type['key'],
                    'title' => $type['title'],
                    'description' => $type['description'],
                    'enabled' => $preferences[$type['key']] ?? true,
                ])
                ->values(),
        ]);
    }

    /**
     * Update the user's notification preferences.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'preferences' => ['array'],
            'preferences.*' => ['boolean', Rule::in([true, false])],
        ]);

        $request->user()->update([
            'notification_preferences' => $validated['preferences'] ?? [],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Notification preferences updated.')]);

        return back();
    }
}
