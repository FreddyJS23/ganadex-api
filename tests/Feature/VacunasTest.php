<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vacuna;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class VacunasTest extends TestCase
{
    use RefreshDatabase;

    private array $vacuna = [
        'nombre' => 'vacuna',
        'intervalo_dosis' => 33,
        'dosis_recomendada_anual' => 2,
        'tipo_vacuna' => 'medica',
        'aplicable_a_todos' => false,
        'tipo_ganados' => [
            ['ganado_tipo_id' => 1, 'sexo' => 'M'],
            ['ganado_tipo_id' => 2, 'sexo' => 'M'],
            ['ganado_tipo_id' => 3, 'sexo' => 'M'],
            ['ganado_tipo_id' => 4, 'sexo' => 'M'],
            ['ganado_tipo_id' => 1, 'sexo' => 'H'],
            ['ganado_tipo_id' => 2, 'sexo' => 'H'],
        ]
    ];
    private array $vacunaAplicableTodos = [
        'nombre' => 'vacuna',
        'intervalo_dosis' => 33,
        'dosis_recomendada_anual' => 2,
        'tipo_vacuna' => 'medica',
        'aplicable_a_todos' => true,
    ];

    private int $cantidad_vacunas = 10;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->hasConfiguracion()->create();
    }

    private function generarVacunas(): Collection
    {
        return Vacuna::factory()
            ->count($this->cantidad_vacunas)
            ->create();
    }
    public static function ErrorInputProvider(): array
    {
        return [
            'caso de insertar datos erróneos' => [
                [
                    'nombre' => 'te',
                    'intervalo_dosis' => 'd32',
                    'dosis_recomendada_anual' => 'invalid',
                    'tipo_vacuna' => 'invalid',
                    'aplicable_a_todos' => 'invalid',
                    'tipo_ganados' => [
                        ['ganado_tipo_id' => 999, 'sexo' => 'invalid']
                    ]
                ],
                ['nombre', 'intervalo_dosis', 'dosis_recomendada_anual', 'tipo_vacuna', 'aplicable_a_todos', 'tipo_ganados.0.ganado_tipo_id', 'tipo_ganados.0.sexo']
            ],
            'caso de no insertar datos requeridos' => [
                [],
                ['nombre', 'intervalo_dosis', 'tipo_vacuna']
            ],
        ];
    }



    /**
     * A basic feature test example.
     */

    public function test_obtener_vacunas(): void
    {
        $this->generarVacunas();

        $response = $this->actingAs($this->user)->getJson(route('vacunas.index'));
        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
            $json->whereType('vacunas', 'array')
                ->has(
                    'vacunas.0',
                    fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                    => $json->whereAllType([
                        'id' => 'integer',
                        'nombre' => 'string',
                        'intervalo_dosis' => 'integer',
                        'dosis_recomendada_anual' => 'integer',
                        'tipo_vacuna' => 'string',
                        'aplicable_a_todos' => 'boolean',
                        'tipos_ganado' => 'array|null'
                    ])
                )
                //tipo vacuna Leptospirosis aplicable a tipo ganado novillo y adulto
                ->has(
                    'vacunas.3.tipos_ganado.0',
                    fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                    => $json->whereAllType([
                        'id' => 'integer',
                        'tipo' => 'string',
                        'sexo' => 'string'
                    ])
                )
        );
    }


    public function test_creacion_vacuna(): void
    {

        $response = $this->actingAs($this->user)->postJson(route('vacunas.store'), $this->vacuna);

        $response->assertStatus(201)->assertJson(
            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
            $json->has(
                'vacuna',
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                $json->whereAllType([
                    'id' => 'integer',
                    'nombre' => 'string',
                    'intervalo_dosis' => 'integer',
                    'dosis_recomendada_anual' => 'integer',
                    'tipo_vacuna' => 'string',
                    'aplicable_a_todos' => 'boolean',
                    'tipos_ganado' => 'array'
                ])
                    ->has('tipos_ganado', 6, fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json->whereAllType(['id' => 'integer', 'tipo' => 'string', 'sexo' => 'string']))
            )
        );
    }
    public function test_creacion_vacuna_aplicable_a_todos(): void
    {

        $response = $this->actingAs($this->user)->postJson(route('vacunas.store'), $this->vacunaAplicableTodos);

        $response->assertStatus(201)->assertJson(
            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
            $json->has(
                'vacuna',
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                $json->whereAllType([
                    'id' => 'integer',
                    'nombre' => 'string',
                    'intervalo_dosis' => 'integer',
                    'dosis_recomendada_anual' => 'integer',
                    'tipo_vacuna' => 'string',
                    'aplicable_a_todos' => 'boolean',
                    'tipos_ganado' => 'null'
                ])
            )
        );
    }


    public function test_obtener_vacuna(): void
    {
        $vacunas = $this->generarVacunas();
        $idRandom = random_int(0, $this->cantidad_vacunas - 1);
        $idVacuna = $vacunas[$idRandom]->id;

        $response = $this->actingAs($this->user)->getJson(sprintf('api/vacunas/%s', $idVacuna));

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
            $json->has(
                'vacuna',
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                => $json->whereAllType([
                    'id' => 'integer',
                    'nombre' => 'string',
                    'intervalo_dosis' => 'integer',
                    'dosis_recomendada_anual' => 'integer',
                    'tipo_vacuna' => 'string',
                    'aplicable_a_todos' => 'boolean',
                    'tipos_ganado' => 'array|null'
                ])
            )
        );
    }

    public function test_actualizar_vacuna(): void
    {
        $vacunas = $this->generarVacunas();
        $idRandom = random_int(0, $this->cantidad_vacunas - 1);
        $idVacunaEditar = $vacunas[$idRandom]->id;

        $response = $this->actingAs($this->user)->putJson(sprintf('api/vacunas/%s', $idVacunaEditar), $this->vacuna);

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
            $json->has(
                'vacuna',
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                $json->where('nombre', $this->vacuna['nombre'])
                    ->where('intervalo_dosis', $this->vacuna['intervalo_dosis'])
                    ->where('dosis_recomendada_anual', $this->vacuna['dosis_recomendada_anual'])
                    ->where('tipo_vacuna', $this->vacuna['tipo_vacuna'])
                    ->where('aplicable_a_todos', $this->vacuna['aplicable_a_todos'])
                    ->etc()
            )
        );
    }


    public function test_eliminar_vacuna(): void
    {
        $vacunas = $this->generarVacunas();
        $idRandom = random_int(0, $this->cantidad_vacunas - 1);
        $idToDelete = $vacunas[$idRandom]->id;


        $response = $this->actingAs($this->user)->deleteJson(sprintf('api/vacunas/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['vacunaID' => $idToDelete]);
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_vacuna(array $vacuna, array $errores): void
    {

        $response = $this->actingAs($this->user)->postJson('api/vacunas', $vacuna);

        $response->assertStatus(422)->assertInvalid($errores);
    }
}
