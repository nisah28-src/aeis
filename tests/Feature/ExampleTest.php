<?php

test('the application returns a successful response', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

test('the jobs listing page is accessible', function () {
    $response = $this->get('/jobs');

    $response->assertStatus(200);
});
