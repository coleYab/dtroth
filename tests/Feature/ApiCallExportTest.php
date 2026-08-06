<?php

use App\Models\ApiCall;
use App\Models\App;
use App\Models\User;

use function Pest\Laravel\actingAs;

function exportCsvRows(string $content): array
{
    $rows = array_map('str_getcsv', explode("\n", trim($content)));

    return array_values(array_filter($rows, fn (array $row) => $row !== ['']));
}

test('api calls can be exported as csv with a header row', function () {
    $user = User::factory()->create();
    $app = App::factory()->create(['user_id' => $user->id]);
    ApiCall::factory()->count(3)->success()->create(['app_id' => $app->id]);
    actingAs($user);

    $response = $this->get(route('apps.logs.export', $app))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
        ->assertDownload('api-log-'.now()->format('Y-m-d').'.csv');

    $rows = exportCsvRows($response->streamedContent());

    expect($rows[0])->toBe([
        'Timestamp',
        'Status',
        'Method',
        'Path',
        'Query type',
        'Query value',
        'Result',
        'Response time (ms)',
    ])
        ->and($rows)->toHaveCount(4);
});

test('the export respects the status filter', function () {
    $user = User::factory()->create();
    $app = App::factory()->create(['user_id' => $user->id]);
    ApiCall::factory()->count(2)->success()->create(['app_id' => $app->id]);
    ApiCall::factory()->count(2)->failed()->create(['app_id' => $app->id]);
    actingAs($user);

    $rows = exportCsvRows($this->get(route('apps.logs.export', ['app' => $app, 'status' => 'failed']))->streamedContent());

    expect($rows)->toHaveCount(3);

    foreach (array_slice($rows, 1) as $row) {
        expect((int) $row[1])->toBeGreaterThanOrEqual(400);
    }
});

test('the export respects the date range filter', function () {
    $user = User::factory()->create();
    $app = App::factory()->create(['user_id' => $user->id]);
    ApiCall::factory()->success()->on(now()->subDays(5))->create(['app_id' => $app->id]);
    ApiCall::factory()->success()->on(now()->subDays(60))->create(['app_id' => $app->id]);
    actingAs($user);

    $rows = exportCsvRows($this->get(route('apps.logs.export', [
        'app' => $app,
        'from' => now()->subDays(10)->toDateString(),
    ]))->streamedContent());

    expect($rows)->toHaveCount(2);
});

test('the export is scoped to the owners apps', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $app = App::factory()->create(['user_id' => $other->id]);
    ApiCall::factory()->count(3)->create(['app_id' => $app->id]);
    actingAs($owner);

    $this->get(route('apps.logs.export', $app))->assertNotFound();
});
