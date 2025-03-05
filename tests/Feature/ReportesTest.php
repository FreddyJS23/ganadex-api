<?php

namespace Tests\Feature;

use App\Models\Ganado;
use App\Models\Hacienda;
use App\Models\User;
use Database\Seeders\DemostracionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ReportesTest extends TestCase
{


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

    public function test_reporte_individual_vaca(): void
    {
        $this->withHeader('origin', config('app.url'))->postJson('api/login', [
            'usuario' => 'admin',
            'password' => 'admin',
        ]);

        //para distinguir vacas de los demas deberia tener una relacion con parto
        $ganadoRandom=Ganado::where('sexo','H')->has('parto')->get()->random();

       $response=$this->actingAs($this->user)->getJson(route('reportes.ganado',['ganado'=>$ganadoRandom->id]));


       $response->assertHeader('content-type', 'application/pdf')
       ->assertHeader('content-disposition', 'inline; filename="document.pdf"');

    }

    public function test_reporte_venta_ganado(): void
    {
        $this->withHeader('origin', config('app.url'))->postJson('api/login', [
            'usuario' => 'admin',
            'password' => 'admin',
        ]);

       $response=$this->actingAs($this->user)->getJson(route('reportes.ventaGanado'));


       $response->assertHeader('content-type', 'application/pdf')
       ->assertHeader('content-disposition', 'inline; filename="document.pdf"');

    }

    public function test_reporte_causas_fallecimiento(): void
    {
        $this->withHeader('origin', config('app.url'))->postJson('api/login', [
            'usuario' => 'admin',
            'password' => 'admin',
        ]);

       $response=$this->actingAs($this->user)->getJson(route('reportes.fallecimientos'));


       $response->assertHeader('content-type', 'application/pdf')
       ->assertHeader('content-disposition', 'inline; filename="document.pdf"');

    }

    public function test_reporte_natalidad(): void
    {
        $this->withHeader('origin', config('app.url'))->postJson('api/login', [
            'usuario' => 'admin',
            'password' => 'admin',
        ]);

       $response=$this->actingAs($this->user)->postJson(route('reportes.natalidad'));


       $response->assertHeader('content-type', 'application/pdf')
       ->assertHeader('content-disposition', 'inline; filename="resumenNatalidad.pdf"');

    }

    public function test_reporte_nota_de_venta_al_vender_ganado(): void
    {
        $this->withHeader('origin', config('app.url'))->postJson('api/login', [
            'usuario' => 'admin',
            'password' => 'admin',
        ]);

       $response=$this->actingAs($this->user)->getJson(route('reportes.facturaVentaGanado'));


       $response->assertHeader('content-type', 'application/pdf')
       ->assertHeader('content-disposition', 'inline; filename="document.pdf"');

    }
}
