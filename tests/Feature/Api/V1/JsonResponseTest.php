<?php

test('api errors return json even without an accept header', function () {
    // Plain get() sends Accept: text/html — api/* must still respond with JSON.
    $this->get('/api/v1/feeds')
        ->assertUnauthorized()
        ->assertHeader('content-type', 'application/json')
        ->assertJsonStructure(['message']);
});

test('api 404 returns the status text as the message', function () {
    $this->getJson('/api/v1/does-not-exist')
        ->assertNotFound()
        ->assertJsonPath('message', 'Not Found');
});
