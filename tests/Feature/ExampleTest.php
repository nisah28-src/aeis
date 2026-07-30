<?php

test('the application returns a successful response', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

test('the jobs listing page is accessible', function () {
    $response = $this->get('/jobs');

    $response->assertStatus(200);
});

test('the job detail page shows apply, save and track actions for candidates', function () {
    $response = $this->get('/jobs/1');

    $response->assertStatus(200);
    $response->assertSee('Apply now');
    $response->assertSee('Save for later');
    $response->assertSee('Track application');
});
