<?php

test('the application returns a successful response on home page', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
