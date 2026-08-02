<?php

use App\Enums\SupportTicketCategory;
use App\Mail\SupportTicketReceived;
use App\Models\App;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    actingAs($this->user);
});

test('the support page shows the categories and the developers apps', function () {
    $app = App::factory()->create(['user_id' => $this->user->id]);

    $this->get(route('support.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('support')
            ->has('categories', count(SupportTicketCategory::cases()))
            ->where('categories.0.value', 'billing')
            ->has('apps', 1)
            ->where('apps.0.id', $app->id));
});

test('submitting a ticket stores it and redirects with a reference', function () {
    Mail::fake();

    $app = App::factory()->create(['user_id' => $this->user->id]);

    $this->post(route('support.store'), [
        'subject' => 'Sandbox returns 429 errors',
        'category' => 'technical_issue',
        'app_id' => $app->id,
        'description' => 'My sandbox requests started failing with 429 errors at around noon. Please help.',
    ])
        ->assertRedirect(route('support.index'))
        ->assertSessionHas('ticket.reference');

    $ticket = SupportTicket::query()->first();

    expect($ticket)->not->toBeNull()
        ->and($ticket->user_id)->toBe($this->user->id)
        ->and($ticket->app_id)->toBe($app->id)
        ->and($ticket->category)->toBe(SupportTicketCategory::TechnicalIssue)
        ->and($ticket->status->value)->toBe('new')
        ->and($ticket->reference)->toMatch('/^TKT-[A-Z0-9]{8}$/')
        ->and($ticket->reference)->toBe(session('ticket.reference'));

    Mail::assertQueued(SupportTicketReceived::class, fn ($mail) => $mail->ticket->id === $ticket->id);
});

test('ticket references are unique', function () {
    Mail::fake();

    for ($i = 0; $i < 5; $i++) {
        $this->post(route('support.store'), [
            'subject' => 'Unique reference please '.$i,
            'category' => 'other',
            'description' => 'This ticket exists only to verify that references are unique across submissions.',
        ])->assertRedirect();
    }

    expect(SupportTicket::query()->count())->toBe(5)
        ->and(SupportTicket::query()->pluck('reference')->unique()->count())->toBe(5);
});

test('description must be at least 20 characters', function () {
    $this->post(route('support.store'), [
        'subject' => 'A short problem',
        'category' => 'account',
        'description' => 'Too short.',
    ])->assertSessionHasErrors('description');
});

test('an attachment is stored when provided', function () {
    Mail::fake();
    Storage::fake('local');

    $this->post(route('support.store'), [
        'subject' => 'Screenshot attached',
        'category' => 'technical_issue',
        'description' => 'Attaching a screenshot of the error dialog that appears in the dashboard.',
        'attachment' => UploadedFile::fake()->image('error.png', 800, 600),
    ])->assertRedirect();

    $ticket = SupportTicket::query()->first();

    expect($ticket->attachment_path)->not->toBeNull()
        ->and(Storage::disk('local')->exists($ticket->attachment_path))->toBeTrue();
});

test('a non-image non-pdf attachment is rejected', function () {
    $this->post(route('support.store'), [
        'subject' => 'Bad attachment',
        'category' => 'other',
        'description' => 'This ticket carries a file that should not be accepted by the validation rules.',
        'attachment' => UploadedFile::fake()->create('evil.exe', 100),
    ])->assertSessionHasErrors('attachment');
});

test('an app owned by another developer cannot be attached', function () {
    $other = App::factory()->create(['user_id' => User::factory()->create()->id]);

    $this->post(route('support.store'), [
        'subject' => 'Wrong app',
        'category' => 'account',
        'app_id' => $other->id,
        'description' => 'This ticket references an app that belongs to a different developer account entirely.',
    ])->assertSessionHasErrors('app_id');
});

test('a ticket can be submitted without a related app', function () {
    Mail::fake();

    $this->post(route('support.store'), [
        'subject' => 'General question',
        'category' => 'feature_request',
        'description' => 'I would like to request support for additional age query operators in the API.',
    ])->assertRedirect();

    expect(SupportTicket::query()->first()->app_id)->toBeNull();
});
