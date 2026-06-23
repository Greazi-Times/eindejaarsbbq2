<?php

it('allows same-origin state-changing requests', function () {
    $this->withHeaders([
        'Origin' => config('app.url'),
        'Sec-Fetch-Site' => 'same-origin',
    ])->post(route('logout'))->assertRedirect();
});

it('rejects a state-changing request with an untrusted origin', function () {
    $this->withHeaders([
        'Origin' => 'https://attacker.example',
        'Sec-Fetch-Site' => 'cross-site',
    ])->post(route('logout'))->assertForbidden();
});

it('allows safe cross-origin navigation requests', function () {
    $this->withHeaders([
        'Sec-Fetch-Site' => 'cross-site',
    ])->get(route('terms'))->assertRedirect('/legal#terms');
});
