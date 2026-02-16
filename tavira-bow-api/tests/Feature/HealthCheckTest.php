<?php

it('returns health status with database and redis', function () {
    $response = $this->getJson('/api/health');

    $response->assertOk()
        ->assertJsonStructure(['status', 'database', 'redis', 'timestamp'])
        ->assertJson(['status' => 'ok', 'database' => 'connected']);
});
