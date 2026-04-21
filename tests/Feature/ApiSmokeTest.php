<?php

it('returns translations from the public api', function () {
    $response = $this->getJson('/api/get-trans');

    $response->assertOk();
    expect($response->json())->toBeArray();
});

it('requires authentication for processing batch status', function () {
    $this->getJson('/api/processing/batch/00000000-0000-0000-0000-000000000000')
        ->assertStatus(401);
});
