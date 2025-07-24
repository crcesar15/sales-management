<?php

declare(strict_types=1);

it('returns a successful response', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});
