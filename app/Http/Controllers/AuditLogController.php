<?php

namespace App\Http\Controllers;

use App\Http\Resources\AuditLogResource;
use App\Models\App;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    /**
     * Show the account-wide audit log page.
     */
    public function index(Request $request): Response
    {
        $action = $request->string('action')->toString();
        $appId = $request->string('app')->toString();
        $from = $request->date('from');
        $to = $request->date('to');

        $logs = AuditLog::query()
            ->with(['app:id,name', 'user:id,name'])
            ->when($action !== '', fn (Builder $query) => $query->where('action', $action))
            ->when($appId !== '', fn (Builder $query) => $query->where('app_id', $appId))
            ->when($from !== null, fn (Builder $query) => $query->whereDate('created_at', '>=', $from))
            ->when($to !== null, fn (Builder $query) => $query->whereDate('created_at', '<=', $to))
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        $apps = collect([['id' => null, 'name' => 'No app']])
            ->concat($request->user()->apps()->get(['id', 'name'])->map(fn (App $app) => ['id' => $app->id, 'name' => $app->name]));

        return Inertia::render('audit-logs', [
            'logs' => AuditLogResource::collection($logs),
            'apps' => $apps->values(),
            'actions' => $this->actionOptions(),
            'filters' => [
                'action' => $action,
                'app' => $appId,
                'from' => $from?->toDateString(),
                'to' => $to?->toDateString(),
            ],
        ]);
    }

    /**
     * Export the account-wide audit log as CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $action = $request->string('action')->toString();
        $appId = $request->string('app')->toString();
        $from = $request->date('from');
        $to = $request->date('to');

        $logs = AuditLog::query()
            ->with(['app:id,name', 'user:id,name'])
            ->when($action !== '', fn (Builder $query) => $query->where('action', $action))
            ->when($appId !== '', fn (Builder $query) => $query->where('app_id', $appId))
            ->when($from !== null, fn (Builder $query) => $query->whereDate('created_at', '>=', $from))
            ->when($to !== null, fn (Builder $query) => $query->whereDate('created_at', '<=', $to))
            ->latest('created_at')
            ->limit(10000)
            ->get();

        return response()->streamDownload(function () use ($logs): void {
            $out = fopen('php://output', 'wb');

            if ($out === false) {
                return;
            }

            fputcsv($out, ['Timestamp', 'Action', 'Description', 'App', 'Actor']);

            foreach ($logs as $log) {
                fputcsv($out, [
                    $log->created_at->toDateTimeString(),
                    $log->action,
                    $log->description,
                    $log->app->name ?? '',
                    $log->user->name,
                ]);
            }

            fclose($out);
        }, 'audit-log-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * The available audit action filter options.
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function actionOptions(): array
    {
        return [
            ['value' => AuditLog::ACTION_APP_CREATED, 'label' => 'App created'],
            ['value' => AuditLog::ACTION_APP_DELETED, 'label' => 'App deleted'],
            ['value' => AuditLog::ACTION_KEY_CREATED, 'label' => 'Key created'],
            ['value' => AuditLog::ACTION_KEY_ROTATED, 'label' => 'Key rotated'],
            ['value' => AuditLog::ACTION_KEY_REVOKED, 'label' => 'Key revoked'],
            ['value' => AuditLog::ACTION_WEBHOOK_CHANGED, 'label' => 'Webhook changed'],
            ['value' => AuditLog::ACTION_PRESET_CHANGED, 'label' => 'Age-query preset changed'],
            ['value' => AuditLog::ACTION_VERIFICATION_CHANGED, 'label' => 'Verification changed'],
        ];
    }
}
