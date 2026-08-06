<?php

use App\Models\User;
use Symfony\Component\Yaml\Yaml;

use function Pest\Laravel\actingAs;

test('the openapi spec downloads as YAML for authenticated users', function () {
    actingAs(User::factory()->create());

    $this->get(route('docs.openapi'))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/yaml; charset=utf-8')
        ->assertHeader('Content-Disposition', 'attachment; filename=openapi.yaml')
        ->assertDownload('openapi.yaml');
});

test('the openapi spec parses and describes every public endpoint', function () {
    actingAs(User::factory()->create());

    $spec = Yaml::parse($this->get(route('docs.openapi'))->getContent());

    expect($spec['openapi'])->toBe('3.1.0')
        ->and($spec['info']['title'])->toBe('dtroth Verify API')
        ->and($spec['paths'])->toHaveKeys([
            '/v1/verify/age',
            '/v1/verify/session',
            '/v1/verify/{query_id}',
            '/v1/consents/{consent_id}/revoke',
        ])
        ->and($spec['components']['securitySchemes']['bearerAuth']['type'])->toBe('http')
        ->and($spec['components']['securitySchemes']['bearerAuth']['scheme'])->toBe('bearer')
        ->and($spec['components']['schemas']['Error'])->not->toBeNull()
        ->and($spec['components']['responses']['TooManyRequests'])->not->toBeNull();
});

test('the openapi spec requires a bearer token on every path', function () {
    actingAs(User::factory()->create());

    $spec = Yaml::parse($this->get(route('docs.openapi'))->getContent());

    expect($spec['security'])->toBe([['bearerAuth' => []]]);
});

test('the openapi spec is also available as JSON', function () {
    actingAs(User::factory()->create());

    $this->get(route('docs.openapi.json'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/json')
        ->assertJsonPath('openapi', '3.1.0')
        ->assertJsonPath('info.title', 'dtroth Verify API');
});

test('the openapi routes require authentication', function () {
    $this->get(route('docs.openapi'))->assertRedirect(route('login'));
    $this->get(route('docs.openapi.json'))->assertRedirect(route('login'));
});
