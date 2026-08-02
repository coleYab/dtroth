<?php

namespace App\Http\Controllers;

use App\Services\DocsService;
use Inertia\Inertia;
use Inertia\Response;

class DocsController extends Controller
{
    /**
     * Show the API reference.
     */
    public function index(DocsService $docs): Response
    {
        return Inertia::render('docs', [
            'sections' => $docs->sections(),
            'searchIndex' => $docs->searchIndex(),
        ]);
    }

    /**
     * Show the quickstart guide.
     */
    public function quickstart(DocsService $docs): Response
    {
        return Inertia::render('docs/quickstart', [
            'content' => $docs->quickstart(),
            'sections' => $docs->sections(),
            'searchIndex' => $docs->searchIndex(),
        ]);
    }
}
