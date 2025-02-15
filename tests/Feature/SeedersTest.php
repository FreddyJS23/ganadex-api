<?php

namespace Tests\Feature;

use Database\Seeders\DemostracionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SeedersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_seed_demostracion(): void
    {
        $this->seed(DemostracionSeeder::class);

        $this->assertDatabaseHas('users', ['usuario' => 'admin']);
    }
}
