<?php

namespace Tests\Feature\Common;

use App\Models\Estado;
use Illuminate\Database\Eloquent\Collection;

trait NeedsEstado
{
    /** @var Collection<int, Estado> */
    private Collection $estado;
    private Estado $estadoSano;
    private Estado $estadoVendido;
    private Estado $estadoGestacion;
    private Estado $estadoFallecido;
    private Estado $estadoPendienteServicio;
    private Estado $estadoPendienteRevision;
    private Estado $estadoPendientePesajeLeche;
    private Estado $estadoPendienteCapar;
    private Estado $estadoPendienteNumeracion;

    protected function setUp(): void
    {
        $this->estado = Estado::all();
        $this->estadoSano = Estado::find(1);
        $this->estadoVendido = Estado::find(2);
        $this->estadoGestacion = Estado::find(3);
        $this->estadoFallecido = Estado::find(5);
        $this->estadoPendienteServicio = Estado::find(7);
        $this->estadoPendienteRevision = Estado::find(6);
        $this->estadoPendienteNumeracion = Estado::find(9);
        $this->estadoPendienteCapar = Estado::find(10);
        $this->estadoPendientePesajeLeche = Estado::find(11);

    }
}
