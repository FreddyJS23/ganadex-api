<?php

namespace Tests\Feature;

use App\Models\Ganado;
use App\Models\Hacienda;
use App\Models\User;
use App\Models\Venta;
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

    private function headersResponse($response,$nombreArchivoPdf)
    {
        $nombreHacienda=strtoupper($this->hacienda->nombre);

        $nombreArchivoPdf=sprintf('inline; filename="%s hacienda %s.pdf"',$nombreArchivoPdf,$nombreHacienda);

        return $response->assertHeader('content-type', 'application/pdf')
        ->assertHeader('content-disposition', $nombreArchivoPdf);
    }



    public function test_reporte_general(): void
    {
        $this->withHeader('origin', config('app.url'))->postJson('api/login', [
            'usuario' => 'admin',
            'password' => 'admin',
        ]);

       $response=$this->actingAs($this->user)->getJson(route('reportes.general'));


       $this->headersResponse($response,"Reporte general " . now()->format('d-m-Y'));

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


       $this->headersResponse($response,"Resumen vaca " . $ganadoRandom->numero ?? '');

    }

    public function test_reporte_venta_ganado(): void
    {
        $this->withHeader('origin', config('app.url'))->postJson('api/login', [
            'usuario' => 'admin',
            'password' => 'admin',
        ]);

       $response=$this->actingAs($this->user)->getJson(route('reportes.ventaGanado'));

        $this->headersResponse($response, "Resumen ventas de animales año " . now()->format('Y'));
    }

    public function test_reporte_causas_fallecimiento(): void
    {
        $queryStart='2021-01-01';
        $queryEnd='2021-01-31';

        $this->withHeader('origin', config('app.url'))->postJson('api/login', [
            'usuario' => 'admin',
            'password' => 'admin',
        ]);

       $response=$this->actingAs($this->user)->getJson(route('reportes.fallecimientos',['start'=>$queryStart,'end'=>$queryEnd]));

        $this->headersResponse($response, "Resumen causas de fallecimientos " . $queryStart . " - " . $queryEnd);
    }

    public function test_reporte_natalidad(): void
    {
        $this->withHeader('origin', config('app.url'))->postJson('api/login', [
            'usuario' => 'admin',
            'password' => 'admin',
        ]);

       $response=$this->actingAs($this->user)->postJson(route('reportes.natalidad'));

       $this->headersResponse($response,"Resumen natalidad año " . now()->format('Y'));
    }

    public function test_reporte_nota_de_venta_al_vender_ganado(): void
    {
        $this->withHeader('origin', config('app.url'))->postJson('api/login', [
            'usuario' => 'admin',
            'password' => 'admin',
        ]);

        $ventaGanado = Venta::where('hacienda_id', session('hacienda_id'))
        ->orderBy('fecha', 'desc')
        ->first();

        $ganado = $ventaGanado->ganado;

       $response=$this->actingAs($this->user)->getJson(route('reportes.facturaVentaGanado'));

       $this->headersResponse($response,"Nota de venta ganado " .  $ganado->numero ?? '');

    }
}
