<?php

test('api errors return json even without an accept header', function () {
    // Plain get() sends Accept: text/html — api/* must still respond with JSON.
    $this->get('/api/v1/feeds')
        ->assertUnauthorized()
        ->assertHeader('content-type', 'application/json')
        ->assertJsonStructure(['message']);
});
