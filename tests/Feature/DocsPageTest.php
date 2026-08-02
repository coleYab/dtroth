<?php

use App\Models\DocsFeedback;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::factory()->create());
});

test('the docs page renders every reference section', function () {
    $this->get(route('docs.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('docs')
            ->has('sections', 6)
            ->where('sections.0.slug', 'introduction')
            ->where('sections.2.slug', 'age-query-endpoint')
            ->where('sections.2.title', 'Age Query Endpoint')
            ->where('sections.2.html', fn ($html) => str_contains($html, '<table'))
            ->where('sections.2.html', fn ($html) => str_contains($html, 'data-label="Status"'))
            ->where('sections.2.html', fn ($html) => str_contains($html, '<h2 id='))
            ->where('sections.2.html', fn ($html) => str_contains($html, '/docs/authentication'))
            ->has('searchIndex', 6));
});

test('the quickstart page renders the guide content', function () {
    $this->get(route('docs.quickstart'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('docs/quickstart')
            ->where('content.slug', 'quickstart')
            ->where('content.html', fn ($html) => str_contains($html, 'Step 1')));
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
