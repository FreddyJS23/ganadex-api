<?php

namespace Tests\Feature;

use App\Models\Hacienda;
use App\Models\User;
use Database\Seeders\DemostracionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ReportesTest extends TestCase
{
    use RefreshDatabase;


    private $hacienda;
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        if(Hacienda::count()==0) $this->seed(DemostracionSeeder::class);

        $this->hacienda=Hacienda::first();

        $this->user=User::firstWhere('usuario','admin');

    }


    public function test_reporte_general(): void
    {
        $this->withHeader('origin', config('app.url'))->postJson('api/login', [
            'usuario' => 'admin',
            'password' => 'admin',
        ]);

       $response=$this->actingAs($this->user)->getJson(route('reportes.general'));


       $response->assertHeader('content-type', 'application/pdf')
       ->assertHeader('content-disposition', 'inline; filename="document.pdf"');

    }
}
