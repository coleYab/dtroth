<?php

namespace App\Http\Controllers;

use App\Enums\ActivityEventType;
use App\Http\Resources\ActivityEventResource;
use App\Models\ActivityEvent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    /**
     * Show the paginated activity log.
     */
    public function index(Request $request): Response
    {
        $filters = [
            'type' => $request->string('type')->toString(),
            'from' => $request->string('from')->toString(),
            'to' => $request->string('to')->toString(),
        ];

        $type = ActivityEventType::tryFrom($filters['type']);

        $events = ActivityEvent::query()
            ->whereHas('app', fn ($query) => $query->where('user_id', $request->user()->id))
            ->with('app')
            ->when($type !== null, fn ($query) => $query->where('type', $type))
            ->when($filters['from'] !== '', fn ($query) => $query->whereDate('created_at', '>=', $filters['from']))
            ->when($filters['to'] !== '', fn ($query) => $query->whereDate('created_at', '<=', $filters['to']))
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('dashboard/activity', [
            'events' => ActivityEventResource::collection($events),
            'filters' => $filters,
        ]);
    }
}
