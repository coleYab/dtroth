<?php

namespace App\Http\Controllers;

use App\Services\DocsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\Yaml\Yaml;

class DocsController extends Controller
{
    /**
     * Show the API reference.
     */
    public function index(DocsService $docs): InertiaResponse
    {
        return Inertia::render('docs', [
            'sections' => $docs->sections(),
            'searchIndex' => $docs->searchIndex(),
        ]);
    }

    /**
     * Show the quickstart guide.
     */
    public function quickstart(DocsService $docs): InertiaResponse
    {
        return Inertia::render('docs/quickstart', [
            'content' => $docs->quickstart(),
            'sections' => $docs->sections(),
            'searchIndex' => $docs->searchIndex(),
        ]);
    }

    /**
     * Download the OpenAPI specification (YAML).
     */
    public function openapi(): Response
    {
        return new Response(file_get_contents(public_path('openapi.yaml')), 200, [
            'Content-Type' => 'text/yaml',
            'Content-Disposition' => 'attachment; filename=openapi.yaml',
        ]);
    }

    /**
     * Download the OpenAPI specification (JSON).
     */
    public function openapiJson(): JsonResponse
    {
        $spec = Yaml::parseFile(public_path('openapi.yaml'));

        return response()->json($spec);
    }
}
