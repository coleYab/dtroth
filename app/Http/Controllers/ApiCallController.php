<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiCallResource;
use App\Http\Resources\AppResource;
use App\Models\ApiCall;
use App\Models\App;
use App\Services\TeamService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApiCallController extends Controller
{
    /**
     * Show the raw API call logs for an application.
     */
    public function index(Request $request, App $app): Response
    {
        $app = $this->ownedApp($request, $app);

        $calls = $this->filteredQuery($request, $app)
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('apps/logs', [
            'app' => (new AppResource($app->load('environments')))->resolve(),
            'calls' => ApiCallResource::collection($calls),
            'permissions' => TeamService::permissionsFor($request->user(), $app),
            'filters' => [
                'query_type' => $request->string('query_type')->toString(),
                'status' => $request->string('status')->toString(),
                'from' => $request->date('from')?->toDateString(),
                'to' => $request->date('to')?->toDateString(),
            ],
        ]);
    }

    /**
     * Export the application's API calls as CSV, respecting the same
     * filters as the index page.
     */
    public function export(Request $request, App $app): StreamedResponse
    {
        $app = $this->ownedApp($request, $app);

        $calls = $this->filteredQuery($request, $app)
            ->latest('created_at')
            ->limit(10000)
            ->get();

        return response()->streamDownload(function () use ($calls): void {
            $out = fopen('php://output', 'wb');

            if ($out === false) {
                return;
            }

            fputcsv($out, [
                'Timestamp',
                'Status',
                'Method',
                'Path',
                'Query type',
                'Query value',
                'Result',
                'Response time (ms)',
            ]);

            foreach ($calls as $call) {
                fputcsv($out, [
                    $call->created_at->toDateTimeString(),
                    $call->status_code,
                    $call->method,
                    $call->path,
                    $call->query_type?->value,
                    $call->query_value ?? '',
                    $call->result === null ? '' : ($call->result ? 'true' : 'false'),
                    $call->response_time_ms ?? '',
                ]);
            }

            fclose($out);
        }, 'api-log-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
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

    /**
     * The base call query scoped to the app, with the index/export filters
     * applied.
     *
     * @return Builder<ApiCall>
     */
    private function filteredQuery(Request $request, App $app): Builder
    {
        $queryType = $request->string('query_type')->toString();
        $status = $request->string('status')->toString();
        $from = $request->date('from');
        $to = $request->date('to');

        return ApiCall::query()
            ->where('app_id', $app->id)
            ->when($queryType !== '' && in_array($queryType, ['equal_above', 'equal_below'], true), function (Builder $builder) use ($queryType) {
                $builder->where('query_type', $queryType);
            })
            ->when($status === 'success', fn (Builder $builder) => $builder->whereBetween('status_code', [200, 299]))
            ->when($status === 'failed', fn (Builder $builder) => $builder->whereNotBetween('status_code', [200, 299]))
            ->when($from !== null, fn (Builder $builder) => $builder->whereDate('created_at', '>=', $from))
            ->when($to !== null, fn (Builder $builder) => $builder->whereDate('created_at', '<=', $to));
    }
}
