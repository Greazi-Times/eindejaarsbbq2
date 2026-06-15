<?php

test('authorized panel users can open the users resource', function () {
    $user = panelUser(['ViewAny:User']);

    $response = $this
        ->actingAs($user)
        ->get(route('filament.dashboard.resources.users.index'));

    $response->assertOk();
});
