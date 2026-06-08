<?php

use Inertia\Testing\AssertableInertia as Assert;

test('legal page displays the required policies', function () {
    $this->get(route('legal'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Legal')
            ->where('updatedAt', 'March-02-2026')
            ->where('contactEmail', 'privacy@eindejaarsbbq.nl')
            ->has('policies', 3)
            ->where('policies.0.slug', 'terms')
            ->where('policies.1.slug', 'privacy')
            ->where('policies.2.slug', 'cookies')
        );
});

test('legacy legal urls redirect to the legal page anchors', function (string $route, string $target) {
    $this->get(route($route))->assertRedirect($target);
})->with([
    ['terms', '/legal#terms'],
    ['privacy', '/legal#privacy'],
    ['cookies', '/legal#cookies'],
]);
