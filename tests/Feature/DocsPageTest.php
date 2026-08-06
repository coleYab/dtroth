<?php

use App\Models\DocsFeedback;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::factory()->create());
});

test('the docs page renders the quickstart and every reference section', function () {
    $this->get(route('docs.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('docs')
            ->has('sections', 9)
            ->where('sections.0.slug', 'quickstart')
            ->where('sections.0.html', fn ($html) => str_contains($html, 'Step 1'))
            ->where('sections.1.slug', 'introduction')
            ->where('sections.3.slug', 'age-query-endpoint')
            ->where('sections.3.title', 'Age Query Endpoint')
            ->where('sections.3.html', fn ($html) => str_contains($html, '<table'))
            ->where('sections.3.html', fn ($html) => str_contains($html, 'data-label="Status"'))
            ->where('sections.3.html', fn ($html) => str_contains($html, '<h2 id='))
            ->where('sections.3.html', fn ($html) => str_contains($html, '/docs/authentication'))
            ->has('searchIndex', 9));
});

test('docs feedback can be recorded as helpful', function () {
    $this->post(route('docs.feedback'), [
        'slug' => 'age-query-endpoint',
        'helpful' => true,
    ])->assertRedirect();

    $feedback = DocsFeedback::query()->first();

    expect($feedback)->not->toBeNull()
        ->and($feedback->doc_slug)->toBe('age-query-endpoint')
        ->and($feedback->helpful)->toBeTrue()
        ->and($feedback->user_id)->toBe(auth()->id());
});

test('docs feedback can include a comment', function () {
    $this->post(route('docs.feedback'), [
        'slug' => 'error-codes',
        'helpful' => false,
        'comment' => 'Please document the webhook signature algorithm with a full example.',
    ])->assertRedirect();

    expect(DocsFeedback::query()->first()->comment)->toBe('Please document the webhook signature algorithm with a full example.');
});

test('docs feedback rejects unknown slugs', function () {
    $this->post(route('docs.feedback'), [
        'slug' => 'not-a-real-section',
        'helpful' => true,
    ])->assertSessionHasErrors('slug');
});

test('guests can view the docs page', function () {
    auth()->logout();

    $this->get(route('docs.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('docs')->has('sections', 9));
});

test('docs feedback can be recorded by guests', function () {
    auth()->logout();

    $this->post(route('docs.feedback'), [
        'slug' => 'age-query-endpoint',
        'helpful' => true,
    ])->assertRedirect();

    $feedback = DocsFeedback::query()->first();

    expect($feedback)->not->toBeNull()
        ->and($feedback->doc_slug)->toBe('age-query-endpoint')
        ->and($feedback->helpful)->toBeTrue()
        ->and($feedback->user_id)->toBeNull();
});
