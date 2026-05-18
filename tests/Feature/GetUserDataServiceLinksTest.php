<?php

use App\Models\Service;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('returns service_links from services.link for getUserData', function () {
    $service = Service::create([
        'name' => 'Voice',
        'description' => 'test',
        'link' => 'voice',
        'image' => null,
        'status' => 1,
    ]);

    $user = User::factory()->create([
        'services' => [$service->id],
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/getUserData');

    $response->assertOk();
    expect($response->json('service_links'))->toBe(['voice']);
    expect($response->json('services'))->toBe([$service->id]);
});
