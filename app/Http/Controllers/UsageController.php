<?php

namespace App\Http\Controllers;

use App\Http\Resources\AppResource;
use App\Models\App;
use App\Services\UsageService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UsageController extends Controller
{
    /**
     * Show the usage analytics page for an application.
     */
    public function index(Request $request, App $app, UsageService $usage): Response
    {
        $app = $this->ownedApp($request, $app);

        [$from, $to, $range] = $this->resolveRange($request);

        $metrics = $usage->forApp($app, $from, $to);

        return Inertia::render('apps/usage', [
            'app' => (new AppResource($app->load('environments')))->resolve(),
            'range' => $range,
            'metrics' => $metrics,
            'rangeLabel' => $from->format('M j, Y').' — '.$to->format('M j, Y'),
        ]);
    }

    /**
     * Resolve the selected range into from/to dates.
     *
     * @return array{Carbon\CarbonInterface, Carbon\CarbonInterface, string}
     */
    private function resolveRange(Request $request): array
    {
        $range = $request->string('range')->toString();
        $from = $request->date('from');
        $to = $request->date('to');

        if (in_array($range, ['7', '30', '90'], true)) {
            return [now()->subDays((int) $range)->startOfDay(), now(), $range];
        }

        if ($from !== null && $to !== null) {
            return [$from->startOfDay(), $to->endOfDay(), 'custom'];
        }

        return [now()->subDays(29)->startOfDay(), now(), '30'];
    }

    /**
     * Resolve the app owned by the authenticated user.
     */
    private function ownedApp(Request $request, App $app): App
    {
        return $request->user()->apps()->findOrFail($app->id);
    }
}
