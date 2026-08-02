<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiCallResource;
use App\Http\Resources\AppResource;
use App\Models\App;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApiCallController extends Controller
{
    /**
     * Show the raw API call logs for an application.
     */
    public function index(Request $request, App $app): Response
    {
        $app = $this->ownedApp($request, $app);

        $queryType = $request->string('query_type')->toString();
        $status = $request->string('status')->toString();
        $from = $request->date('from');
        $to = $request->date('to');

        $calls = $app->calls()
            ->when($queryType !== '' && in_array($queryType, ['equal_above', 'equal_below'], true), function (Builder $builder) use ($queryType) {
                $builder->where('query_type', $queryType);
            })
            ->when($status === 'success', fn (Builder $builder) => $builder->whereBetween('status_code', [200, 299]))
            ->when($status === 'failed', fn (Builder $builder) => $builder->whereNotBetween('status_code', [200, 299]))
            ->when($from !== null, fn (Builder $builder) => $builder->whereDate('created_at', '>=', $from))
            ->when($to !== null, fn (Builder $builder) => $builder->whereDate('created_at', '<=', $to))
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('apps/logs', [
            'app' => (new AppResource($app->load('environments')))->resolve(),
            'calls' => ApiCallResource::collection($calls),
            'filters' => [
                'query_type' => $queryType,
                'status' => $status,
                'from' => $from?->toDateString(),
                'to' => $to?->toDateString(),
            ],
        ]);
    }

    /**
     * Resolve the app owned by the authenticated user.
     */
    private function ownedApp(Request $request, App $app): App
    {
        return $request->user()->apps()->findOrFail($app->id);
    }
}
