<?php

namespace App\Http\Controllers;

use App\Models\DocsFeedback;
use App\Services\DocsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DocsFeedbackController extends Controller
{
    /**
     * Record a "was this helpful?" vote for a doc section.
     */
    public function store(Request $request, DocsService $docs): RedirectResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', Rule::in(collect($docs->sections())->pluck('slug')->push('quickstart'))],
            'helpful' => ['required', 'boolean'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        DocsFeedback::create([
            'user_id' => $request->user()?->id,
            'doc_slug' => $validated['slug'],
            'helpful' => $validated['helpful'],
            'comment' => $validated['comment'] ?? null,
        ]);

        return back();
    }
}
