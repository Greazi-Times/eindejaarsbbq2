<?php

it('adds baseline browser security headers', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), display-capture=(), geolocation=(), microphone=(), payment=(), usb=()');
});
