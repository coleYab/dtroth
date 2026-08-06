<?php

namespace App\Http\Controllers;

use App\Enums\WebhookDeliveryStatus;
use App\Http\Requests\StoreWebhookRequest;
use App\Http\Requests\UpdateWebhookRequest;
use App\Http\Resources\AppResource;
use App\Http\Resources\WebhookDeliveryResource;
use App\Http\Resources\WebhookResource;
use App\Jobs\SendWebhookDelivery;
use App\Models\App;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Models\WebhookEvent;
use App\Services\TeamService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WebhookController extends Controller
{
    /**
     * Show the webhooks page for an application.
     */
    public function index(Request $request, App $app): Response
    {
        $app = $this->ownedApp($request, $app, 'view');

        return Inertia::render('apps/webhooks/index', $this->indexPayload($request, $app));
    }

    /**
     * Show the create webhook page.
     */
    public function create(Request $request, App $app): Response
    {
        $app = $this->ownedApp($request, $app, 'manage_webhooks');

        return Inertia::render('apps/webhooks/create', [
            'app' => (new AppResource($app->load('environments')))->resolve(),
            'availableEvents' => $this->availableEvents(),
            'permissions' => TeamService::permissionsFor($request->user(), $app),
        ]);
    }

    /**
     * Store a newly created webhook.
     */
    public function store(StoreWebhookRequest $request, App $app): Response
    {
        $app = $this->ownedApp($request, $app, 'manage_webhooks');

        $secret = 'whsec_'.str()->random(48);

        $webhook = $app->webhooks()->create([
            'url' => $request->string('url')->trim()->toString(),
            'secret' => $secret,
            'secret_preview' => $this->preview($secret),
            'description' => $request->string('description')->trim()->toString() ?: null,
        ]);

        $this->syncEvents($webhook, $request->input('events', []));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Webhook created.'),
        ]);

        return Inertia::render('apps/webhooks/index', [
            ...$this->indexPayload($request, $app),
            'revealedSecret' => [
                'id' => $webhook->id,
                'url' => $webhook->url,
                'secret' => $secret,
            ],
        ]);
    }

    /**
     * Update the webhook.
     */
    public function update(UpdateWebhookRequest $request, App $app, Webhook $webhook): RedirectResponse
    {
        $app = $this->ownedApp($request, $app, 'manage_webhooks');

        $webhook = $app->webhooks()->findOrFail($webhook->id);

        $webhook->update([
            'url' => $request->string('url')->trim()->toString(),
            'description' => $request->string('description')->trim()->toString() ?: null,
        ]);

        $this->syncEvents($webhook, $request->input('events', []));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Webhook updated.'),
        ]);

        return redirect()->back();
    }

    /**
     * Remove the webhook.
     */
    public function destroy(Request $request, App $app, Webhook $webhook): RedirectResponse
    {
        $app = $this->ownedApp($request, $app, 'manage_webhooks');

        $app->webhooks()->findOrFail($webhook->id)->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Webhook deleted.'),
        ]);

        return redirect()->route('apps.webhooks.index', ['app' => $app->id]);
    }

    /**
     * Regenerate the signing secret, returning it once.
     */
    public function regenerateSecret(Request $request, App $app, Webhook $webhook): Response
    {
        $app = $this->ownedApp($request, $app, 'manage_secrets');

        $webhook = $app->webhooks()->with('events')->findOrFail($webhook->id);

        $secret = 'whsec_'.str()->random(48);

        $webhook->update([
            'secret' => $secret,
            'secret_preview' => $this->preview($secret),
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Signing secret regenerated.'),
        ]);

        return Inertia::render('apps/webhooks/logs', [
            ...$this->logsPayload($request, $app, $webhook),
            'revealedSecret' => [
                'id' => $webhook->id,
                'url' => $webhook->url,
                'secret' => $secret,
            ],
        ]);
    }

    /**
     * Show the delivery logs for a webhook.
     */
    public function logs(Request $request, App $app, Webhook $webhook): Response
    {
        $app = $this->ownedApp($request, $app, 'view');

        $webhook = $app->webhooks()->with('events')->findOrFail($webhook->id);

        return Inertia::render('apps/webhooks/logs', $this->logsPayload($request, $app, $webhook));
    }

    /**
     * Retry a failed delivery.
     */
    public function retryDelivery(Request $request, App $app, Webhook $webhook, WebhookDelivery $delivery): RedirectResponse
    {
        $app = $this->ownedApp($request, $app, 'manage_webhooks');

        $webhook = $app->webhooks()->findOrFail($webhook->id);

        $delivery = $webhook->deliveries()->findOrFail($delivery->id);

        if ($delivery->status !== WebhookDeliveryStatus::Failed) {
            abort(422, 'Only failed deliveries can be retried.');
        }

        $delivery->update([
            'status' => WebhookDeliveryStatus::Pending,
            'attempts' => 0,
            'response_status' => null,
            'response_body' => null,
        ]);

        SendWebhookDelivery::dispatch($delivery);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Delivery retry queued.'),
        ]);

        return redirect()->back();
    }

    /**
     * The shared payload for the webhooks list page.
     *
     * @return array<string, mixed>
     */
    private function indexPayload(Request $request, App $app): array
    {
        $webhooks = $app->webhooks()
            ->with('events')
            ->withCount([
                'deliveries as recent_failure_count' => fn (Builder $query) => $query
                    ->where('status', WebhookDeliveryStatus::Failed)
                    ->where('created_at', '>=', now()->subHours(24)),
            ])
            ->latest()
            ->get();

        $recentEventCounts = WebhookDelivery::query()
            ->whereIn('webhook_id', $webhooks->pluck('id'))
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('event, COUNT(*) as total')
            ->groupBy('event')
            ->orderByDesc('total')
            ->pluck('total', 'event')
            ->all();

        return [
            'app' => (new AppResource($app->load('environments')))->resolve(),
            'webhooks' => WebhookResource::collection($webhooks),
            'availableEvents' => $this->availableEvents(),
            'recentEventCounts' => $recentEventCounts,
            'permissions' => TeamService::permissionsFor($request->user(), $app),
        ];
    }

    /**
     * The shared payload for the webhook logs page.
     *
     * @return array<string, mixed>
     */
    private function logsPayload(Request $request, App $app, Webhook $webhook): array
    {
        $status = $request->string('status')->toString();
        $from = $request->date('from');
        $to = $request->date('to');

        $deliveries = $webhook->deliveries()
            ->when($status !== '' && in_array($status, ['success', 'failed', 'retrying', 'pending'], true), function (Builder $query) use ($status) {
                $query->where('status', $status);
            })
            ->when($from !== null, fn (Builder $query) => $query->whereDate('created_at', '>=', $from))
            ->when($to !== null, fn (Builder $query) => $query->whereDate('created_at', '<=', $to))
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();

        return [
            'app' => (new AppResource($app->load('environments')))->resolve(),
            'webhook' => (new WebhookResource($webhook->load('events')))->resolve(),
            'deliveries' => WebhookDeliveryResource::collection($deliveries),
            'permissions' => TeamService::permissionsFor($request->user(), $app),
            'filters' => [
                'status' => $status,
                'from' => $from?->toDateString(),
                'to' => $to?->toDateString(),
            ],
        ];
    }

    /**
     * Sync the webhook's subscribed events.
     *
     * @param  array<int, string>  $events
     */
    private function syncEvents(Webhook $webhook, array $events): void
    {
        $ids = WebhookEvent::query()
            ->whereIn('name', $events)
            ->pluck('id');

        $webhook->events()->sync($ids);
    }

    /**
     * All available webhook events.
     *
     * @return array<int, array{name: string, label: string}>
     */
    private function availableEvents(): array
    {
        return WebhookEvent::query()
            ->orderBy('label')
            ->get()
            ->map(fn (WebhookEvent $event) => [
                'name' => $event->name,
                'label' => $event->label,
            ])
            ->values()
            ->all();
    }

    /**
     * Build a masked preview for a signing secret.
     */
    private function preview(string $secret): string
    {
        return 'whsec_••••••••'.substr($secret, -4);
    }

    /**
     * Resolve the app the authenticated user can access with the given
     * permission.
     */
    private function ownedApp(Request $request, App $app, string $permission = 'view'): App
    {
        $app = TeamService::appForMember($request->user(), $app->id);
        TeamService::authorize($request->user(), $app, $permission);

        return $app;
    }
}
